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

use DateTimeImmutable;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\EventSubscriber\RequestListener;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

/**
 * @group functional
 * @covers \SolidInvoice\SaasBundle\EventSubscriber\RequestListener
 */
final class RequestListenerTest extends KernelTestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use EnsureApplicationInstalled;

    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            RequestEvent::class => 'onRequest',
            \Symfony\Component\HttpKernel\Event\ResponseEvent::class => 'onResponse',
        ], RequestListener::getSubscribedEvents());
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
        self::assertStringContainsString('Pending Page', $response->getContent());
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
        self::assertStringContainsString('Paused Page', $response->getContent());
    }

    public function testOnRequestWithCancelledStatusAfterEndDate(): void
    {
        $now = new DateTimeImmutable('2024-01-15');
        $endDate = new DateTimeImmutable('2024-01-10');
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
        self::assertStringContainsString('Cancelled Page', $response->getContent());
    }

    public function testOnRequestWithCancelledStatusBeforeEndDate(): void
    {
        $now = new DateTimeImmutable('2024-01-10');
        $endDate = new DateTimeImmutable('2024-01-15');
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

    public function testOnRequestWithTrialStatusAfterEndDate(): void
    {
        $now = new DateTimeImmutable('2024-01-15');
        $endDate = new DateTimeImmutable('2024-01-10');
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
        self::assertStringContainsString('Trial Expired Page', $response->getContent());
    }

    public function testOnRequestWithExpiredTrialPassesCouponCodeToTemplate(): void
    {
        $now = new DateTimeImmutable('2024-01-15');
        $endDate = new DateTimeImmutable('2024-01-10');
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

    public function testOnRequestWithTrialStatusBeforeEndDate(): void
    {
        $now = new DateTimeImmutable('2024-01-10');
        $endDate = new DateTimeImmutable('2024-01-15');
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
    }

    private function createListener(
        ?User $user = null,
        ?DateTimeImmutable $now = null,
        ?Subscription $subscription = null,
        string $couponCode = '',
        ?callable $onTrialExpiredRender = null,
    ): RequestListener {
        // Get real services from container
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $companyRepository = self::getContainer()->get(CompanyRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        // Mock PlanRepository — the Plan entity isn't part of the default
        // test kernel's Doctrine mapping, so we can't fetch a real one here.
        $planRepository = M::mock(PlanRepositoryInterface::class);
        $planRepository->shouldReceive('findAllOrdered')->andReturn([]);

        // Mock SubscriptionProviderInterface
        $subscriptionManager = M::mock(SubscriptionProviderInterface::class);
        if ($subscription !== null) {
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
            ->with(M::pattern('/@SolidInvoiceSaas\/_alert_banner\.html\.twig/'), M::any())
            ->andReturn('<div class="alert">Banner</div>');

        // Mock Security
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->andReturn($user);

        // Mock Clock to control time in tests
        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn($now ?? new DateTimeImmutable());

        return new RequestListener(
            $companySelector,
            $companyRepository,
            $subscriptionManager,
            $planRepository,
            $twig,
            $security,
            $urlGenerator,
            $clock,
            $couponCode,
        );
    }

    private function createSubscription(
        SubscriptionStatus $status,
        ?DateTimeImmutable $endDate = null
    ): Subscription {
        // Create real Plan object
        $plan = new Plan();
        $plan->setName('Test Plan');
        $plan->setPlanId('test-plan-' . Ulid::generate());
        $plan->setPrice(1000);

        // Create real Subscription object
        $subscription = new Subscription();
        $subscription->setSubscriber($this->company);
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(new DateTimeImmutable('2024-01-01'));
        $subscription->setEndDate($endDate ?? new DateTimeImmutable('2024-12-31'));

        return $subscription;
    }
}
