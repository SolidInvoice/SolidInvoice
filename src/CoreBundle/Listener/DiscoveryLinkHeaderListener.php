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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class DiscoveryLinkHeaderListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Skip API, MCP, and well-known endpoints — they advertise themselves.
        if (str_starts_with($path, '/api') || str_starts_with($path, '/_mcp') || str_starts_with($path, '/.well-known')) {
            return;
        }

        $response = $event->getResponse();

        if (! str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'html')) {
            return;
        }

        $links = [
            sprintf('<%s>; rel="api-catalog"; type="application/linkset+json"', $this->urlGenerator->generate('api_well_known_catalog', [], UrlGeneratorInterface::ABSOLUTE_URL)),
            sprintf('<%s>; rel="service-desc"; type="application/ld+json"', $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABSOLUTE_URL)),
            sprintf('<%s>; rel="service-doc"; type="text/html"', $this->urlGenerator->generate('api_doc', ['_format' => 'html'], UrlGeneratorInterface::ABSOLUTE_URL)),
            sprintf('<%s>; rel="oauth-authorization-server"', $this->urlGenerator->generate('mcp_well_known_authorization_server', [], UrlGeneratorInterface::ABSOLUTE_URL)),
            sprintf('<%s>; rel="mcp-server-card"', $this->urlGenerator->generate('mcp_well_known_server_card', [], UrlGeneratorInterface::ABSOLUTE_URL)),
        ];

        foreach ($links as $link) {
            $response->headers->set('Link', $link, false);
        }
    }
}
