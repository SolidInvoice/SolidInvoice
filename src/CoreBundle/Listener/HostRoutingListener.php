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

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\CoreBundle\Company\CompanyDomainResolver;
use SolidInvoice\CoreBundle\Company\HostType;
use SolidInvoice\CoreBundle\Company\ResolvedHost;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use function in_array;
use function str_starts_with;

/**
 * Resolves the inbound Host header against `SOLIDINVOICE_APPLICATION_URL` and the per-company
 * `custom_domain` column so downstream listeners can short-circuit company selection, and so
 * unknown hosts return 404 instead of leaking the multi-tenant selector.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Listener\HostRoutingListenerTest
 */
final class HostRoutingListener implements EventSubscriberInterface
{
    public const REQUEST_ATTR = '_resolved_host';

    public const PRIORITY = 30;

    private const SELECTOR_ROUTES = [
        '_select_company',
        '_switch_company',
        '_create_company',
        '_onboarding',
    ];

    public function __construct(
        private readonly CompanyDomainResolver $resolver,
        private readonly RouterInterface $router,
        private readonly ?string $installed = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', self::PRIORITY],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest() || $this->installed === null) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isInstallerRequest($request)) {
            return;
        }

        $resolved = $this->resolver->resolve($request->getHost());

        if ($resolved->type === HostType::Unknown) {
            throw new NotFoundHttpException();
        }

        $request->attributes->set(self::REQUEST_ATTR, $resolved);

        if ($resolved->isCustomDomain()) {
            $this->syncRouterContext($resolved);

            if ($this->isSelectorRoute($request)) {
                throw new NotFoundHttpException();
            }
        }
    }

    private function isInstallerRequest(Request $request): bool
    {
        $route = (string) $request->attributes->get('_route', '');

        if (str_starts_with($route, '_install') || $route === '_system_install') {
            return true;
        }

        return str_starts_with($request->getPathInfo(), '/install');
    }

    private function isSelectorRoute(Request $request): bool
    {
        return in_array(
            (string) $request->attributes->get('_route', ''),
            self::SELECTOR_ROUTES,
            true,
        );
    }

    private function syncRouterContext(ResolvedHost $resolved): void
    {
        $context = $this->router->getContext();
        $context->setHost($resolved->host);
        $context->setScheme($resolved->scheme);

        if ($resolved->scheme === 'https') {
            $context->setHttpsPort($resolved->port);
        } else {
            $context->setHttpPort($resolved->port);
        }
    }
}
