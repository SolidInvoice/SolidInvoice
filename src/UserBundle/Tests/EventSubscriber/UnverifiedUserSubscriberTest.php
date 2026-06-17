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

namespace SolidInvoice\UserBundle\Tests\EventSubscriber;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\EventSubscriber\UnverifiedUserSubscriber;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(UnverifiedUserSubscriber::class)]
final class UnverifiedUserSubscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const string NOTICE_URL = '/verify/pending';

    public function testRedirectsUnverifiedUserOnGuardedRoute(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(false),
            saasEnabled: true,
            route: '_dashboard',
        );

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(self::NOTICE_URL, $response->getTargetUrl());
    }

    public function testDoesNotActWhenNotSaas(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(false),
            saasEnabled: false,
            route: '_dashboard',
        );

        self::assertFalse($event->hasResponse());
    }

    public function testDoesNotActForVerifiedUser(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(true),
            saasEnabled: true,
            route: '_dashboard',
        );

        self::assertFalse($event->hasResponse());
    }

    public function testDoesNotActForAnonymous(): void
    {
        $event = $this->dispatch(
            user: null,
            saasEnabled: true,
            route: '_dashboard',
        );

        self::assertFalse($event->hasResponse());
    }

    public function testAllowsVerificationNoticeRoute(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(false),
            saasEnabled: true,
            route: '_verify_email_notice',
        );

        self::assertFalse($event->hasResponse());
    }

    public function testAllowsTwoFactorRoutes(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(false),
            saasEnabled: true,
            route: '_2fa_login',
        );

        self::assertFalse($event->hasResponse());
    }

    public function testReturns403ForXhrRequest(): void
    {
        $event = $this->dispatch(
            user: new User()->setVerified(false),
            saasEnabled: true,
            route: '_dashboard',
            xhr: true,
        );

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    private function dispatch(?UserInterface $user, bool $saasEnabled, string $route, bool $xhr = false): RequestEvent
    {
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->andReturn($user);

        $toggle = M::mock(ToggleInterface::class);
        $toggle->shouldReceive('isActive')->with('saas_enabled')->andReturn($saasEnabled);

        $urlGenerator = M::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->with('_verify_email_notice')->andReturn(self::NOTICE_URL);

        $subscriber = new UnverifiedUserSubscriber($security, $toggle, $urlGenerator);

        $request = Request::create('/dashboard');
        $request->attributes->set('_route', $route);
        if ($xhr) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        $event = new RequestEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $subscriber->onKernelRequest($event);

        return $event;
    }
}
