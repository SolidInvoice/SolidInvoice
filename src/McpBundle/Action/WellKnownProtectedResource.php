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

use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class WellKnownProtectedResource
{
    // RFC 9728 §3.1: metadata for the /_mcp resource lives at the path-suffixed URL; the bare one stays for older clients.
    #[Route(path: '/.well-known/oauth-protected-resource', name: 'mcp_well_known_protected_resource', methods: ['GET'])]
    #[Route(path: '/.well-known/oauth-protected-resource/_mcp', name: 'mcp_well_known_protected_resource_mcp', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $base = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        return new JsonResponse([
            'resource' => $base . '/_mcp',
            'authorization_servers' => [$base],
            'bearer_methods_supported' => ['header'],
            'scopes_supported' => McpScope::values(),
        ]);
    }
}
