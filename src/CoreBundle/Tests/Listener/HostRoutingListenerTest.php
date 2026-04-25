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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanyDomainResolver;
use SolidInvoice\CoreBundle\Company\HostType;
use SolidInvoice\CoreBundle\Company\ResolvedHost;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Listener\HostRoutingListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \SolidInvoice\CoreBundle\Listener\HostRoutingListener
 */
final class HostRoutingListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSubscribesToKernelRequestAtPriority32(): void
    {
        $events = HostRoutingListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
        self::assertSame(['onKernelRequest', 32], $events[KernelEvents::REQUEST]);
    }

    public function testSkipsWhenNotInstalled(): void
    {
        $resolver = M::mock(CompanyDomainResolver::class);
        $resolver->shouldNotReceive('resolve');

        $listener = new HostRoutingListener($resolver, $this->router(), null);

        $request = Request::create('https://anything.example/');
        $listener->onKernelRequest($this->event($request));

        self::assertFalse($request->attributes->has(HostRoutingListener::REQUEST_ATTR));
    }

    public function testSkipsInstallerRoute(): void
    {
        $resolver = M::mock(CompanyDomainResolver::class);
        $resolver->shouldNotReceive('resolve');

        $listener = new HostRoutingListener($resolver, $this->router(), '2025');

        $request = Request::create('https://anything.example/install');
        $listener->onKernelRequest($this->event($request));

        self::assertFalse($request->attributes->has(HostRoutingListener::REQUEST_ATTR));
    }

    public function testThrowsNotFoundForUnknownHost(): void
    {
        $resolver = M::mock(CompanyDomainResolver::class);
        $resolver->shouldReceive('resolve')
            ->once()
            ->andReturn(new ResolvedHost(HostType::Unknown, 'rogue.example', 'https', 443));

        $listener = new HostRoutingListener($resolver, $this->router(), '2025');

        $this->expectException(NotFoundHttpException::class);

        $listener->onKernelRequest($this->event(Request::create('https://rogue.example/')));
    }

    public function testStoresResolvedHostAndSyncsRouterContext(): void
    {
        $resolved = new ResolvedHost(HostType::DefaultHost, 'app.example.com', 'https', 443);
        $resolver = M::mock(CompanyDomainResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn($resolved);

        $context = new RequestContext();
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('getContext')->andReturn($context);

        $listener = new HostRoutingListener($resolver, $router, '2025');

        $request = Request::create('https://app.example.com/dashboard');
        $listener->onKernelRequest($this->event($request));

        self::assertSame($resolved, $request->attributes->get(HostRoutingListener::REQUEST_ATTR));
        self::assertSame('app.example.com', $context->getHost());
        self::assertSame('https', $context->getScheme());
        self::assertSame(443, $context->getHttpsPort());
    }

    #[DataProvider('provideSelectorRoutes')]
    public function testThrowsNotFoundForSelectorRouteOnCustomDomain(string $route): void
    {
        $resolved = new ResolvedHost(HostType::CustomDomain, 'acme.example', 'https', 443, new Company());
        $resolver = M::mock(CompanyDomainResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn($resolved);

        $listener = new HostRoutingListener($resolver, $this->router(), '2025');

        $request = Request::create('https://acme.example/select-company');
        $request->attributes->set('_route', $route);

        $this->expectException(NotFoundHttpException::class);

        $listener->onKernelRequest($this->event($request));
    }

    /**
     * @return iterable<array<string>>
     */
    public static function provideSelectorRoutes(): iterable
    {
        yield ['_select_company'];
        yield ['_switch_company'];
        yield ['_create_company'];
        yield ['_onboarding'];
    }

    private function event(Request $request): RequestEvent
    {
        return new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }

    private function router(): RouterInterface
    {
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('getContext')->zeroOrMoreTimes()->andReturn(new RequestContext());
        return $router;
    }
}
