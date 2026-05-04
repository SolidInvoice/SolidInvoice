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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/.well-known/mcp/server-card.json', name: 'mcp_well_known_server_card', methods: ['GET'])]
final class WellKnownServerCard
{
    public function __invoke(Request $request): JsonResponse
    {
        $base = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        return new JsonResponse([
            'serverInfo' => [
                'name' => SolidInvoiceCoreBundle::APP_NAME,
                'version' => SolidInvoiceCoreBundle::VERSION,
            ],
            'transport' => [
                'type' => 'http',
                'endpoint' => $base . '/_mcp',
            ],
            'authorization' => [
                'type' => 'oauth2',
                'metadata' => $base . '/.well-known/oauth-protected-resource',
                'scopes' => McpScope::values(),
            ],
            'capabilities' => [
                'tools' => new \stdClass(),
            ],
        ]);
    }
}
