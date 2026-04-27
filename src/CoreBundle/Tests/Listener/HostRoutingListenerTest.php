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
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
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

    public function testSubscribesToKernelRequestAtPriority30(): void
    {
        $events = HostRoutingListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::REQUEST, $events);
        self::assertSame(['onKernelRequest', 30], $events[KernelEvents::REQUEST]);
    }

    public function testSkipsWhenNotInstalled(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldNotReceive('findOneByCustomDomain');

        $listener = new HostRoutingListener($this->resolver($repository), $this->router(), null);

        $request = Request::create('https://anything.example/');
        $listener->onKernelRequest($this->event($request));

        self::assertFalse($request->attributes->has(HostRoutingListener::REQUEST_ATTR));
    }

    public function testSkipsInstallerRoute(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldNotReceive('findOneByCustomDomain');

        $listener = new HostRoutingListener($this->resolver($repository), $this->router(), '2025');

        $request = Request::create('https://anything.example/install');
        $listener->onKernelRequest($this->event($request));

        self::assertFalse($request->attributes->has(HostRoutingListener::REQUEST_ATTR));
    }

    public function testThrowsNotFoundForUnknownHost(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')->once()->with('rogue.example')->andReturnNull();

        $listener = new HostRoutingListener(
            $this->resolver($repository, 'https://app.example.com'),
            $this->router(),
            '2025',
        );

        $this->expectException(NotFoundHttpException::class);

        $listener->onKernelRequest($this->event(Request::create('https://rogue.example/')));
    }

    public function testStoresResolvedHostAndLeavesDefaultHostContextUntouched(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldNotReceive('findOneByCustomDomain');

        $context = new RequestContext('', 'GET', 'app.example.com', 'http', 8080, 0);
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('getContext')->andReturn($context);

        $listener = new HostRoutingListener(
            $this->resolver($repository, 'https://app.example.com'),
            $router,
            '2025',
        );

        $request = Request::create('http://app.example.com:8080/dashboard');
        $listener->onKernelRequest($this->event($request));

        $resolved = $request->attributes->get(HostRoutingListener::REQUEST_ATTR);
        self::assertInstanceOf(ResolvedHost::class, $resolved);
        self::assertSame(HostType::DefaultHost, $resolved->type);
        // Default host requests must not override Symfony's RouterListener context
        self::assertSame('app.example.com', $context->getHost());
        self::assertSame('http', $context->getScheme());
        self::assertSame(8080, $context->getHttpPort());
    }

    public function testSyncsRouterContextForCustomDomain(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')->once()->with('acme.example')->andReturn(new Company());

        $context = new RequestContext('', 'GET', 'acme.example', 'http', 80, 0);
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('getContext')->andReturn($context);

        $listener = new HostRoutingListener(
            $this->resolver($repository, 'https://app.example.com'),
            $router,
            '2025',
        );

        $request = Request::create('http://acme.example/dashboard');
        $listener->onKernelRequest($this->event($request));

        $resolved = $request->attributes->get(HostRoutingListener::REQUEST_ATTR);
        self::assertInstanceOf(ResolvedHost::class, $resolved);
        self::assertSame(HostType::CustomDomain, $resolved->type);
        self::assertSame('acme.example', $context->getHost());
        self::assertSame('https', $context->getScheme());
        self::assertSame(443, $context->getHttpsPort());
    }

    #[DataProvider('provideSelectorRoutes')]
    public function testThrowsNotFoundForSelectorRouteOnCustomDomain(string $route): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')->once()->with('acme.example')->andReturn(new Company());

        $listener = new HostRoutingListener(
            $this->resolver($repository, 'https://app.example.com'),
            $this->router(),
            '2025',
        );

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

    private function resolver(CompanyRepository $repository, string $applicationUrl = ''): CompanyDomainResolver
    {
        return new CompanyDomainResolver($repository, $applicationUrl);
    }
}
