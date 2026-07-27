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

use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\SaasBundle\EventSubscriber\CompanyEventSubscriber;
use SolidInvoice\SaasBundle\Plan\DefaultPlanProvider;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\UserBundle\Entity\User;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * In paid-trial mode the trial belongs to the payment provider, so signup must
 * not start one locally — it leaves the subscription PENDING (which is what
 * makes RequestListener gate the app) and sends the user to pick a plan.
 *
 * SubscriptionManager is final, so these assert the resulting subscription
 * state rather than mocking the call.
 */
#[CoversClass(CompanyEventSubscriber::class)]
final class CompanyEventSubscriberPaidTrialTest extends TestCase
{
    public function testPaidTrialModeLeavesTheSubscriptionPending(): void
    {
        [, $subscription] = $this->dispatchSignup(BillingModeFactory::paidTrial());

        self::assertSame(SubscriptionStatus::PENDING, $subscription->getStatus());
    }

    public function testPaidTrialModeRedirectsToPlanSelection(): void
    {
        [$event] = $this->dispatchSignup(BillingModeFactory::paidTrial());

        $response = $event->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/billing/subscription/plans', $response->getTargetUrl());
    }

    /**
     * The Trial row is the ledger the onboarding email scheduler joins against.
     * Without it the whole sequence stops, not just the trial-specific emails.
     */
    public function testPaidTrialModeStillRecordsTheTrialLedgerRow(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(false);
        $trialManager->expects(self::once())->method('createTrial')->willReturn(new Trial());

        $this->dispatchSignup(BillingModeFactory::paidTrial(), $trialManager);
    }

    public function testPaidTrialModeDoesNotRecordASecondTrialForTheSameUser(): void
    {
        $trialManager = $this->createMock(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn(true);
        $trialManager->expects(self::never())->method('createTrial');

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
     * @return array{0: ResponseEvent, 1: Subscription}
     */
    private function dispatchSignup(
        BillingMode $billingMode,
        ?TrialManagerInterface $trialManager = null,
    ): array {
        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);
        $plan->setTrialDuration(new DateInterval('P14D'));

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->method('find')->willReturn($plan);
        $planRepository->method('findDefault')->willReturn($plan);

        // SubscriptionManager persists through save() on every mutation, so the
        // last saved entity is the subscription in its final state.
        $saved = null;
        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
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
            $trialManager = $this->createMock(TrialManagerInterface::class);
            $trialManager->method('userHasTrial')->willReturn(false);
            $trialManager->method('createTrial')->willReturn(new Trial());
        }

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $callback): mixed => $callback($entityManager),
        );

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/billing/subscription/plans');

        $subscriber = new CompanyEventSubscriber(
            new DefaultPlanProvider($planRepository),
            $subscriptionManager,
            $security,
            $trialManager,
            $entityManager,
            $urlGenerator,
            $billingMode,
        );

        $company = new Company();
        $event = new CompanyCreatedEvent($company);
        $subscriber->onCompanyCreated($event);

        $responseEvent = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $subscriber->onResponse($responseEvent);

        self::assertInstanceOf(Subscription::class, $saved);

        return [$responseEvent, $saved];
    }
}
