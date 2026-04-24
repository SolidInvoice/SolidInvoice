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

#[Route(path: '/.well-known/oauth-authorization-server', name: 'mcp_well_known_authorization_server', methods: ['GET'])]
final class WellKnownAuthServer
{
    public function __invoke(Request $request): JsonResponse
    {
        $base = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        return new JsonResponse([
            'issuer' => $base,
            'authorization_endpoint' => $base . '/oauth/authorize',
            'token_endpoint' => $base . '/oauth/token',
            'revocation_endpoint' => $base . '/oauth/revoke',
            'registration_endpoint' => $base . '/oauth/register',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => McpScope::values(),
        ]);
    }
}
