<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\SaasBundle\EventSubscriber\CompanyEventSubscriber;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SaasBundle\Plan\DefaultPlanProvider;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepFirst;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Integration\PaymentIntegrationInterface;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;

/**
 * In paid-trial mode the trial belongs to the payment provider, so signup must
 * not start one locally — it leaves the subscription PENDING (which is what
 * makes RequestListener gate the app) and sends the user to the welcome page to
 * activate the default plan.
 *
 * SubscriptionManager is final, so these assert the resulting subscription
 * state rather than mocking the call.
 */
#[CoversClass(CompanyEventSubscriber::class)]
final class CompanyEventSubscriberPaidTrialTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $dispatchedSteps = [];

    public function testPaidTrialModeLeavesTheSubscriptionPending(): void
    {
        [, $subscription] = $this->dispatchSignup(BillingModeFactory::paidTrial());

        self::assertSame(SubscriptionStatus::PENDING, $subscription->getStatus());
    }

    public function testPaidTrialModeRedirectsToTheWelcomePage(): void
    {
        [$event] = $this->dispatchSignup(BillingModeFactory::paidTrial());

        $response = $event->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/billing/subscription/welcome', $response->getTargetUrl());
    }

    public function testAJsonClientKeepsItsResponseWhileTheTrialLedgerIsStillRecorded(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects($this->once())->method('createTrial')->willReturn(new Trial());

        $request = new Request();
        $request->headers->set('Accept', 'application/json');

        $response = new Response('{}', Response::HTTP_CREATED);

        [$event, $subscription] = $this->dispatchSignup(BillingModeFactory::paidTrial(), $trialManager, $request, $response);

        self::assertSame($response, $event->getResponse());
        self::assertSame(SubscriptionStatus::PENDING, $subscription->getStatus());
    }

    /**
     * The Trial row is the ledger the onboarding email scheduler joins against.
     * Without it the whole sequence stops, not just the trial-specific emails.
     */
    public function testPaidTrialModeStillRecordsTheTrialLedgerRow(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects($this->once())->method('createTrial')->willReturn(new Trial());

        $this->dispatchSignup(BillingModeFactory::paidTrial(), $trialManager);
    }

    public function testPaidTrialModeDoesNotRecordASecondTrialForTheSameUser(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(true);
        $trialManager->expects($this->never())->method('createTrial');

        $this->dispatchSignup(BillingModeFactory::paidTrial(), $trialManager);
    }

    /**
     * Free-trial mode must be untouched: a plan with a configured trial still
     * starts one locally at signup, flipping the subscription to TRIAL.
     */
    public function testFreeTrialModeStillStartsATrialLocally(): void
    {
        [, $subscription] = $this->dispatchSignup(BillingModeFactory::freeTrial());

        self::assertSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
    }

    /**
     * The welcome goes out at signup, not at the next hourly scheduler run.
     */
    public function testFreeTrialModeSendsTheWelcomeEmailAtSignup(): void
    {
        $this->dispatchSignup(BillingModeFactory::freeTrial());

        self::assertSame(['first'], $this->dispatchedSteps);
    }

    /**
     * A zero interval (`P0D`) is how prod stored "no trial" on the Free plan.
     * It must not start a trial that is already expired at signup.
     */
    public function testFreeTrialModeDoesNotStartAZeroLengthTrial(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects($this->never())->method('createTrial');

        [, $subscription] = $this->dispatchSignup(BillingModeFactory::freeTrial(), $trialManager, 'P0D');

        self::assertNotSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
        self::assertSame([], $this->dispatchedSteps);
    }

    /**
     * A Free default activates the new company in place: no plan picker, no
     * PENDING subscription, and no Trial row (that would use up the user's
     * one opt-in trial).
     */
    public function testFreeDefaultPlanActivatesTheSubscriptionAtSignup(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects($this->never())->method('createTrial');

        [$event, $subscription] = $this->dispatchSignup(BillingModeFactory::freeTrial(), $trialManager, null, freePlan: true);

        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame(['first'], $this->dispatchedSteps, 'the welcome goes out at signup');
    }

    /**
     * Users who had the old automatic trial used to be sent to the plan picker.
     * On a Free default there is nothing to pick, so they go ACTIVE too.
     */
    public function testFreeDefaultPlanActivatesEvenWhenTheUserHadATrial(): void
    {
        $trialManager = $this->createStub(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(true);

        [$event, $subscription] = $this->dispatchSignup(BillingModeFactory::freeTrial(), $trialManager, null, freePlan: true);

        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * Paid-trial mode sends a new company to checkout, but a Free default has
     * nothing to check out: it would sit PENDING on a page it cannot leave.
     */
    public function testPaidTrialModeStillActivatesAFreeDefault(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects($this->never())->method('createTrial');

        [$event, $subscription] = $this->dispatchSignup(BillingModeFactory::paidTrial(), $trialManager, null, freePlan: true);

        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testPaidTrialModeSendsNoEmailBeforeCheckout(): void
    {
        $this->dispatchSignup(BillingModeFactory::paidTrial());

        self::assertSame([], $this->dispatchedSteps);
    }

    /**
     * @return array{0: ResponseEvent, 1: Subscription}
     */
    private function dispatchSignup(
        BillingMode $billingMode,
        ?TrialManagerInterface $trialManager = null,
        ?string $trialDuration = 'P14D',
        bool $freePlan = false,
    ): array {
        $plan = new Plan();
        $plan->setName($freePlan ? 'Free' : 'Pro');
        $plan->setPlanId($freePlan ? '0' : 'variant-123');
        $plan->setPrice($freePlan ? 0 : 1000);

        if ($trialDuration !== null) {
            $plan->setTrialDuration(new DateInterval($trialDuration));
        }

        $planRepository = $this->createStub(PlanRepositoryInterface::class);
        $planRepository->method('find')->willReturn($plan);
        $planRepository->method('findDefault')->willReturn($plan);

        // SubscriptionManager persists through save() on every mutation, so the
        // last saved entity is the subscription in its final state.
        $saved = null;
        $subscriptionRepository = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->method('save')->willReturnCallback(
            static function (object $entity) use (&$saved): void {
                if ($entity instanceof Subscription) {
                    $saved = $entity;
                }
            },
        );

        $subscriptionManager = new SubscriptionManager(
            $subscriptionRepository,
            $planRepository,
            $this->createStub(PaymentIntegrationInterface::class),
        );

        if (! $trialManager instanceof TrialManagerInterface) {
            $trialManager = $this->createStub(TrialManagerInterface::class);
            $trialManager->method('userHasTrial')->willReturn(false);
            $trialManager->method('createTrial')->willReturn(new Trial());
        }

        $user = new User();
        new ReflectionProperty($user, 'id')->setValue($user, new Ulid());
        // A user signing up was created a moment ago.
        $user->setCreated(CarbonImmutable::now());

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            function (SendOnboardingEmailMessage $message): Envelope {
                $this->dispatchedSteps[] = $message->stepKey;

                return new Envelope($message);
            },
        );

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $callback): mixed => $callback($entityManager),
        );

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route): string => match ($route) {
                'saas_subscription_welcome' => '/billing/subscription/welcome',
                default => '/billing/subscription/plans',
            },
        );

        $subscriber = new CompanyEventSubscriber(
            new DefaultPlanProvider($planRepository),
            $subscriptionManager,
            $security,
            $trialManager,
            $entityManager,
            $urlGenerator,
            $billingMode,
            new OnboardingDispatcher(
                new OnboardingStepRegistry([new StepFirst()]),
                $this->createStub(UserSettingRepositoryInterface::class),
                $messageBus,
                new MockClock(),
                new NullLogger(),
            ),
            new NullLogger(),
            new TrialPeriod(),
        );

        $company = new Company();
        $event = new CompanyCreatedEvent($company);
        $subscriber->onCompanyCreated($event);

        $responseEvent = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $subscriber->onResponse($responseEvent);

        self::assertInstanceOf(Subscription::class, $saved);

        return [$responseEvent, $saved];
    }
}
