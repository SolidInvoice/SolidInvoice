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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\EventSubscriber\RequestListener;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
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
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @dataProvider provideSkippedRoutes
     */
    public function testOnRequestWithSkippedRoute(string $route): void
    {
        $listener = $this->createListener(new User());

        $request = new Request();
        $request->attributes->set('_route', $route);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertStringContainsString('Trial Expired Page', $response->getContent());
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
            $this->createMock(HttpKernelInterface::class),
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
            $this->createMock(HttpKernelInterface::class),
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
        ?Subscription $subscription = null
    ): RequestListener {
        // Get real services from container
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $companyRepository = self::getContainer()->get(CompanyRepository::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        // Mock SubscriptionProviderInterface
        /** @var SubscriptionProviderInterface&MockObject $subscriptionManager */
        $subscriptionManager = $this->createMock(SubscriptionProviderInterface::class);
        if ($subscription !== null) {
            $subscriptionManager
                ->method('getSubscriptionFor')
                ->willReturn($subscription);
        }

        // Mock Twig to return simple HTML for testing
        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturnCallback(static function (string $template): string {
                return match (true) {
                    str_contains($template, 'pending.html.twig') => '<html>Pending Page</html>',
                    str_contains($template, 'paused.html.twig') => '<html>Paused Page</html>',
                    str_contains($template, 'cancelled.html.twig') => '<html>Cancelled Page</html>',
                    str_contains($template, 'trial_expired.html.twig') => '<html>Trial Expired Page</html>',
                    str_contains($template, '_alert_banner.html.twig') => '<div class="alert">Banner</div>',
                    default => '',
                };
            });

        // Mock Security
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        // Mock Clock to control time in tests
        /** @var ClockInterface&MockObject $clock */
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now ?? new DateTimeImmutable());

        return new RequestListener(
            $companySelector,
            $companyRepository,
            $subscriptionManager,
            $twig,
            $security,
            $urlGenerator,
            $clock
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
