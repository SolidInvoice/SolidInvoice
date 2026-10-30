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
use DateTimeImmutable;
use Mockery as M;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Test\Traits\EventListenerAttributeTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\EventSubscriber\RequestListener;
use SolidInvoice\SaasBundle\Message\SendTrialDowngradedEmailMessage;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Service\TrialBannerResolver;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Integration\PaymentIntegrationInterface;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\TraceableMessageBus;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[CoversClass(RequestListener::class)]
#[Group('functional')]
final class RequestListenerTest extends KernelTestCase
{
    use EventListenerAttributeTrait;

    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use EnsureApplicationInstalled;

    private TraceableMessageBus $bus;

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            RequestEvent::class => ['onRequest', 0],
            ResponseEvent::class => ['onResponse', 0],
        ], self::listenedEvents(RequestListener::class));
    }

    public function testOnRequestWithNoUser(): void
    {
        $listener = $this->createListener();

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    #[DataProvider('provideSkippedRoutes')]
    public function testOnRequestWithSkippedRoute(string $route): void
    {
        $listener = $this->createListener(new User());

        $request = new Request();
        $request->attributes->set('_route', $route);

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnRequestWithPendingStatus(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::PENDING);
        $listener = $this->createListener(new User(), subscription: $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('Pending Page', (string) $response->getContent());
    }

    public function testOnRequestWithPendingStatusInPaidTrialModeRedirectsToWelcome(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::PENDING);
        $listener = $this->createListener(
            new User(),
            subscription: $subscription,
            billingMode: BillingModeFactory::paidTrial(),
        );

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertStringContainsString('/subscription/welcome', $response->getTargetUrl());
    }

    public function testOnRequestWithPausedStatus(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::PAUSED);
        $listener = $this->createListener(new User(), subscription: $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('Paused Page', (string) $response->getContent());
    }

    public function testOnRequestWithCancelledStatusAfterEndDate(): void
    {
        $now = CarbonImmutable::parse('2024-01-15');
        $endDate = CarbonImmutable::parse('2024-01-10');
        $subscription = $this->createSubscription(SubscriptionStatus::CANCELLED, $endDate);
        $listener = $this->createListener(new User(), $now, $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('Cancelled Page', (string) $response->getContent());
    }

    public function testOnRequestWithCancelledStatusBeforeEndDate(): void
    {
        $now = CarbonImmutable::parse('2024-01-10');
        $endDate = CarbonImmutable::parse('2024-01-15');
        $subscription = $this->createSubscription(SubscriptionStatus::CANCELLED, $endDate);
        $listener = $this->createListener(new User(), $now, $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnRequestWithExpiredLocalTrialMovesToFreePlan(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2024-01-10'));
        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0);
        $listener = $this->createListener(new User(), CarbonImmutable::parse('2024-01-15'), $subscription, freePlan: $free);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertSame($free, $subscription->getPlan());

        $messages = $this->bus->getDispatchedMessages();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendTrialDowngradedEmailMessage::class, $messages[0]['message']);
    }

    public function testOnRequestWithExpiredLemonSqueezyTrialKeepsTheWall(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2024-01-10'));
        $subscription->setSubscriptionId('ls_sub_123');

        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0);
        $listener = $this->createListener(new User(), CarbonImmutable::parse('2024-01-15'), $subscription, freePlan: $free);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertStringContainsString('Trial Expired Page', (string) $event->getResponse()?->getContent());
        self::assertSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
        self::assertSame([], $this->bus->getDispatchedMessages());
    }

    public function testOnRequestWithExpiredTrialShowsTheWallWhenNoFreePlanExists(): void
    {
        $now = CarbonImmutable::parse('2024-01-15');
        $endDate = CarbonImmutable::parse('2024-01-10');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, $endDate);
        $listener = $this->createListener(new User(), $now, $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('Trial Expired Page', (string) $response->getContent());
    }

    public function testOnRequestWithExpiredTrialPassesCouponCodeToTemplate(): void
    {
        $now = CarbonImmutable::parse('2024-01-15');
        $endDate = CarbonImmutable::parse('2024-01-10');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, $endDate);

        $capturedContext = null;
        $listener = $this->createListener(
            new User(),
            $now,
            $subscription,
            'WELCOME20',
            static function (array $context) use (&$capturedContext): void {
                $capturedContext = $context;
            },
        );

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertIsArray($capturedContext);
        self::assertSame('WELCOME20', $capturedContext['coupon_code']);
    }

    public function testOnRequestWithExpiredTrialPassesCouponPercentToTemplate(): void
    {
        $now = CarbonImmutable::parse('2024-01-15');
        $endDate = CarbonImmutable::parse('2024-01-10');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, $endDate);

        $capturedContext = null;
        $listener = $this->createListener(
            new User(),
            $now,
            $subscription,
            'WELCOME30',
            static function (array $context) use (&$capturedContext): void {
                $capturedContext = $context;
            },
        );

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $listener->onRequest(new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        self::assertIsArray($capturedContext);
        self::assertSame(30, $capturedContext['coupon_percent']);
    }

    public function testOnRequestWithTrialStatusBeforeEndDate(): void
    {
        $now = CarbonImmutable::parse('2024-01-10');
        $endDate = CarbonImmutable::parse('2024-01-15');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, $endDate);
        $listener = $this->createListener(new User(), $now, $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnRequestWithActiveStatus(): void
    {
        $subscription = $this->createSubscription(SubscriptionStatus::ACTIVE);
        $listener = $this->createListener(new User(), subscription: $subscription);

        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnResponseInjectsTheEndingBannerWithinWindow(): void
    {
        // now = 2024-01-01, endDate 5 days out -> ending variant
        $now = CarbonImmutable::parse('2024-01-01');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2024-01-06'));

        $captured = null;
        $listener = $this->createListener(
            new User(),
            $now,
            $subscription,
            onBannerRender: static function (array $context) use (&$captured): void {
                $captured = $context;
            },
        );

        $response = new Response('<div class="page-wrapper">content</div>');
        $event = $this->responseEvent($response);

        $listener->onResponse($event);

        self::assertIsArray($captured);
        self::assertSame('info', $captured['type']);
        self::assertSame('Your Test Plan trial ends on January 6', $captured['title']);
        self::assertSame('Keep Test Plan', $captured['cta_label']);
        // "Keep" checks out the plan being trialled, not whatever plan is default.
        self::assertSame('/billing/subscription/activate?plan=' . $subscription->getPlan()->getPlanId(), $captured['cta_url']);
        self::assertArrayHasKey('code', $captured);
        self::assertNull($captured['code']);
        self::assertStringContainsString('<div class="alert">Banner</div>', (string) $response->getContent());
    }

    public function testOnResponseInjectsCouponBannerInFinalDays(): void
    {
        // now = 2024-01-01, endDate 2 days out + coupon code -> coupon variant
        $now = CarbonImmutable::parse('2024-01-01');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2024-01-03'));

        $captured = null;
        $listener = $this->createListener(
            new User(),
            $now,
            $subscription,
            couponCode: 'SAVE30',
            onBannerRender: static function (array $context) use (&$captured): void {
                $captured = $context;
            },
        );

        $listener->onResponse($this->responseEvent(new Response('<div class="page-wrapper">x</div>')));

        self::assertIsArray($captured);
        self::assertSame('SAVE30', $captured['code']);
        self::assertStringContainsString('30% off', (string) $captured['message']);
        self::assertSame('30% off Test Plan', $captured['title']);
        self::assertSame('Keep Test Plan', $captured['cta_label']);
    }

    public function testOnResponseInjectsNothingWhenOutsideWindow(): void
    {
        // 9 days out -> resolver returns null -> no injection
        $now = CarbonImmutable::parse('2024-01-01');
        $subscription = $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2024-01-10'));

        $listener = $this->createListener(new User(), $now, $subscription);

        $response = new Response('<div class="page-wrapper">x</div>');
        $listener->onResponse($this->responseEvent($response));

        self::assertStringNotContainsString('<div class="alert">Banner</div>', (string) $response->getContent());
    }

    private function responseEvent(Response $response): ResponseEvent
    {
        $request = new Request();
        $request->attributes->set('_route', '_dashboard');

        return new ResponseEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }

    /**
     * @return iterable<array<string>>
     */
    public static function provideSkippedRoutes(): iterable
    {
        yield ['billing_index'];
        yield ['saas_subscription_checkout'];
        yield ['saas_payment_success'];
        yield ['_switch_company'];
        yield ['_view_quote_external'];
        yield ['_view_invoice_external'];

        // A blocked subscription must never trap the user in the app.
        yield ['_logout'];
    }

    private function createListener(
        ?User $user = null,
        ?DateTimeImmutable $now = null,
        ?Subscription $subscription = null,
        string $couponCode = '',
        ?callable $onTrialExpiredRender = null,
        ?callable $onBannerRender = null,
        int $couponPercent = 30,
        ?BillingMode $billingMode = null,
        ?Plan $freePlan = null,
    ): RequestListener {
        // Get real services from container
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $companyRepository = self::getContainer()->get(CompanyRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        // Mock PlanRepository — the Plan entity isn't part of the default
        // test kernel's Doctrine mapping, so we can't fetch a real one here.
        $planRepository = M::mock(PlanRepositoryInterface::class);
        $planRepository->shouldReceive('findAllOrdered')->andReturn([]);
        $planRepository->shouldReceive('findFree')->andReturn($freePlan);

        // Mock SubscriptionProviderInterface
        $subscriptionManager = M::mock(SubscriptionProviderInterface::class);
        if ($subscription instanceof Subscription) {
            $subscriptionManager
                ->shouldReceive('getSubscriptionFor')
                ->andReturn($subscription);
        }

        // Mock Twig to return simple HTML for testing
        $twig = M::mock(Environment::class);
        $twig->shouldReceive('render')
            ->with(M::pattern('/@SolidInvoiceSaas\/subscription\/pending\.html\.twig/'), M::any())
            ->andReturn('<html>Pending Page</html>');
        $twig->shouldReceive('render')
            ->with(M::pattern('/@SolidInvoiceSaas\/subscription\/paused\.html\.twig/'), M::any())
            ->andReturn('<html>Paused Page</html>');
        $twig->shouldReceive('render')
            ->with(M::pattern('/@SolidInvoiceSaas\/subscription\/cancelled\.html\.twig/'), M::any())
            ->andReturn('<html>Cancelled Page</html>');
        $twig->shouldReceive('render')
            ->with(M::pattern('/@SolidInvoiceSaas\/subscription\/trial_expired\.html\.twig/'), M::on(static function (array $context) use ($onTrialExpiredRender): bool {
                if ($onTrialExpiredRender !== null) {
                    $onTrialExpiredRender($context);
                }

                return true;
            }))
            ->andReturn('<html>Trial Expired Page</html>');
        $twig->shouldReceive('render')
            ->with(M::pattern('/@SolidInvoiceSaas\/_alert_banner\.html\.twig/'), M::on(static function (array $context) use ($onBannerRender): bool {
                if ($onBannerRender !== null) {
                    $onBannerRender($context);
                }

                return true;
            }))
            ->andReturn('<div class="alert">Banner</div>');

        // Mock Security
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->andReturn($user);

        // Mock Clock to control time in tests
        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn($now ?? CarbonImmutable::now());

        $translator = self::getContainer()->get(TranslatorInterface::class);

        $billingMode ??= BillingModeFactory::freeTrial($couponCode, $couponPercent);

        $trialBannerResolver = new TrialBannerResolver(
            $clock,
            $billingMode,
            bannerDays: 7,
            couponDays: 2,
        );

        return new RequestListener(
            $companySelector,
            $companyRepository,
            $subscriptionManager,
            $planRepository,
            $twig,
            $security,
            $urlGenerator,
            $clock,
            $trialBannerResolver,
            $translator,
            $billingMode,
            // SubscriptionManager is final readonly; build a real one over stub
            // collaborators so downgradeToFree() runs for real.
            new SubscriptionManager(
                $this->createStub(SubscriptionRepositoryInterface::class),
                $planRepository,
                $this->createStub(PaymentIntegrationInterface::class),
            ),
            $this->bus = new TraceableMessageBus(new MessageBus()),
        );
    }

    private function createSubscription(
        SubscriptionStatus $status,
        ?DateTimeImmutable $endDate = null
    ): Subscription {
        $plan = new Plan();
        $plan->setName('Test Plan');
        $plan->setPlanId('test-plan-' . Ulid::generate());
        $plan->setPrice(1000);
        $plan->setTrialDuration(new DateInterval('P14D'));

        // Create real Subscription object
        $subscription = new Subscription();
        $subscription->setSubscriber($this->company);
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(CarbonImmutable::parse('2024-01-01'));
        $subscription->setEndDate($endDate ?? CarbonImmutable::parse('2024-12-31'));

        return $subscription;
    }
}
