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

namespace SolidInvoice\McpBundle\Security;

use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Matches the MCP requests an anonymous client may make: the handshake and
 * `tools/list`, so anyone can see which tools exist before signing in. Used as
 * a PUBLIC_ACCESS access_control rule; everything else (tools/call included)
 * falls through to ROLE_USER and gets the OAuth 401.
 *
 * A bearer token is still validated when present, so an invalid one is
 * rejected rather than silently downgraded to anonymous.
 */
final class PublicMcpRequestMatcher implements RequestMatcherInterface
{
    private const array PUBLIC_METHODS = [
        'initialize',
        'notifications/initialized',
        'ping',
        'tools/list',
    ];

    public function matches(Request $request): bool
    {
        if (! $request->isMethod(Request::METHOD_POST) || ! str_starts_with($request->getPathInfo(), '/_mcp')) {
            return false;
        }

        try {
            $payload = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (! \is_array($payload) || $payload === []) {
            return false;
        }

        // A JSON-RPC batch is only public when every message in it is.
        $messages = array_is_list($payload) ? $payload : [$payload];
        return array_all($messages, fn ($message) => \is_array($message) && \in_array($message['method'] ?? null, self::PUBLIC_METHODS, true));
    }
}
