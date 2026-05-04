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

namespace SolidInvoice\McpBundle\Action;

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/.well-known/mcp/server-card.json', name: 'mcp_well_known_server_card', methods: ['GET'])]
final class WellKnownServerCard
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'serverInfo' => [
                'name' => SolidInvoiceCoreBundle::APP_NAME,
                'version' => SolidInvoiceCoreBundle::VERSION,
            ],
            'transport' => [
                'type' => 'http',
                'endpoint' => $this->urlGenerator->generate('_mcp_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
            'authorization' => [
                'type' => 'oauth2',
                'metadata' => $this->urlGenerator->generate('mcp_well_known_protected_resource', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'scopes' => McpScope::values(),
            ],
            'capabilities' => [
                'tools' => new \stdClass(),
            ],
        ]);
    }
}
