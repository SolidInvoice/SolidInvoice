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

use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\SaasBundle\EventSubscriber\EmailVerificationBannerListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @covers \SolidInvoice\SaasBundle\EventSubscriber\EmailVerificationBannerListener
 */
final class EmailVerificationBannerListenerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            ResponseEvent::class => ['onResponse', -10],
        ], EmailVerificationBannerListener::getSubscribedEvents());
    }

    public function testInjectsBannerWhenGated(): void
    {
        $gate = M::mock(EmailVerificationGateInterface::class);
        $gate->shouldReceive('isGated')->once()->andReturnTrue();

        $translator = M::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')
            ->with('email_verification.banner.title', [], 'messages')
            ->andReturn('Verify your email');
        $translator->shouldReceive('trans')
            ->with('email_verification.banner.message', [], 'messages')
            ->andReturn('Some features are disabled.');

        $twig = M::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with('@SolidInvoiceSaas/_alert_banner.html.twig', M::on(static function (array $context): bool {
                return ($context['type'] ?? null) === 'warning'
                    && ($context['title'] ?? null) === 'Verify your email'
                    && ($context['message'] ?? null) === 'Some features are disabled.'
                    && ! isset($context['cta_label'])
                    && ! isset($context['cta_url']);
            }))
            ->andReturn('<div class="verification-banner">Verify your email</div>');

        $listener = new EmailVerificationBannerListener($gate, $twig, $translator);

        $request = Request::create('/dashboard', 'GET');
        $response = new Response('<html><body><div class="page-wrapper">content</div></body></html>');

        $event = new ResponseEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onResponse($event);

        $expected = '<html><body><div class="page-wrapper"><div class="verification-banner">Verify your email</div>content</div></body></html>';
        self::assertSame($expected, $response->getContent());
    }

    public function testSkipsWhenNotGated(): void
    {
        $gate = M::mock(EmailVerificationGateInterface::class);
        $gate->shouldReceive('isGated')->once()->andReturnFalse();

        $twig = M::mock(Environment::class);
        $twig->shouldNotReceive('render');

        $translator = M::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('trans');

        $listener = new EmailVerificationBannerListener($gate, $twig, $translator);

        $request = Request::create('/dashboard', 'GET');
        $response = new Response('<html><body><div class="page-wrapper">content</div></body></html>');

        $event = new ResponseEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onResponse($event);

        self::assertStringNotContainsString('verification-banner', $response->getContent());
    }

    public function testSkipsNonGetRequests(): void
    {
        $gate = M::mock(EmailVerificationGateInterface::class);
        $gate->shouldNotReceive('isGated');

        $twig = M::mock(Environment::class);
        $twig->shouldNotReceive('render');

        $translator = M::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('trans');

        $listener = new EmailVerificationBannerListener($gate, $twig, $translator);

        $request = Request::create('/dashboard', 'POST');
        $response = new Response('<html><body><div class="page-wrapper">content</div></body></html>');

        $event = new ResponseEvent(
            M::mock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $listener->onResponse($event);
    }
}
