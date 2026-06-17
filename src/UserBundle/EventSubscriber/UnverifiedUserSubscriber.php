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

namespace SolidInvoice\UserBundle\EventSubscriber;

use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function in_array;
use function str_starts_with;

/**
 * Sandboxes unverified hosted users to the email-verification flow.
 *
 * On hosted (`saas_enabled`) deployments a logged-in user whose email is not
 * verified may only reach the verification notice/resend/logout/2FA routes;
 * every other route redirects to the notice page (or returns 403 for XHR and
 * Live-component requests, which cannot follow a redirect). API and MCP access
 * is blocked separately at authentication by {@see \SolidInvoice\UserBundle\Security\VerifiedUserChecker}.
 *
 * @see \SolidInvoice\UserBundle\Tests\EventSubscriber\UnverifiedUserSubscriberTest
 */
final readonly class UnverifiedUserSubscriber implements EventSubscriberInterface
{
    /**
     * Routes an unverified user may still reach while sandboxed.
     */
    private const array ALLOWED_ROUTES = [
        '_verify_email',
        '_verify_email_notice',
        '_resend_verification',
        '_logout',
    ];

    public function __construct(
        private Security $security,
        private ToggleInterface $toggle,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        // Priority below the firewall (8) so the authentication token is resolved.
        return [KernelEvents::REQUEST => ['onKernelRequest', 6]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest() || $event->hasResponse()) {
            return;
        }

        // The whole sandbox is a hosted-only concern.
        if (! $this->toggle->isActive('saas_enabled')) {
            return;
        }

        $user = $this->security->getUser();

        if (! $user instanceof User || $user->isVerified()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');

        // No route (assets) or developer tooling: never interfere.
        if ($route === '' || str_starts_with($route, '_wdt') || str_starts_with($route, '_profiler')) {
            return;
        }

        if (in_array($route, self::ALLOWED_ROUTES, true) || str_starts_with($route, '_2fa_')) {
            return;
        }

        // XHR and Live-component requests cannot follow a redirect to an HTML page;
        // deny them outright so the client surfaces the gate instead of rendering the page body.
        if ($request->isXmlHttpRequest() || str_starts_with($request->getPathInfo(), '/_components')) {
            $event->setResponse(new Response('Please verify your email address before continuing.', Response::HTTP_FORBIDDEN));

            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('_verify_email_notice')));
    }
}
