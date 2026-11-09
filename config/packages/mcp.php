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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;

// Session store is configurable via SOLIDINVOICE_MCP_SESSION_STORE
// (file | memory | cache | framework):
//   - file: on-disk (default, single-node deployments)
//   - memory: in-process (dev only; resets on restart)
//   - cache: PSR-16 cache pool — set SOLIDINVOICE_MCP_SESSION_CACHE_POOL
//     to a Redis-backed pool for multi-node deployments
//   - framework: Symfony session.handler (shares the app's session storage)
//
// The mcp-bundle reads these values at compile time, so env() placeholders
// can't be used here — read $_ENV / $_SERVER directly (symfony/runtime has
// already loaded .env by the time this config runs).
$env = static fn (string $name, string | int $default): string | int => $_ENV[$name] ?? $_SERVER[$name] ?? $default;

$store = (string) $env('SOLIDINVOICE_MCP_SESSION_STORE', 'file');

return App::config([
    'mcp' => [
        'servers' => [
            'solidinvoice' => [
                'name' => SolidInvoiceCoreBundle::APP_NAME,
                'version' => SolidInvoiceCoreBundle::VERSION,
                'description' => 'Create and send invoices and quotes, manage clients, and track payments in SolidInvoice.',
                // The bundle has no `title` key, so serverInfo carries name only; clients fall back to it.
                'icons' => [
                    ['src' => 'https://solidinvoice.co/logo-512.png', 'mime_type' => 'image/png', 'sizes' => ['512x512']],
                    ['src' => 'https://solidinvoice.co/logo-192.png', 'mime_type' => 'image/png', 'sizes' => ['192x192']],
                ],
                'website_url' => 'https://solidinvoice.co/docs/ai/mcp-server/',
                'instructions' => 'Sign in with OAuth (scopes mcp:read, mcp:write); tools/list needs no login. IDs are base58 ULIDs, and any ULID form is accepted as input. Money is a decimal string in the document\'s currency, e.g. "12.50". Call list_workflow_transitions before any apply_*_transition tool to see which transitions are allowed.',
                'transports' => [
                    'stdio' => false,
                    'http' => true,
                ],
                'http' => [
                    'path' => '/_mcp',
                    // The SDK's DNS-rebinding guard only allows localhost, which 403s every
                    // public request. Hosts can't be listed here (custom domains are
                    // per-company, this is compile-time), and it isn't needed: unknown
                    // hosts already 404 in HostRoutingListener, and auth is a bearer
                    // token, not a cookie a rebound page could ride on.
                    'allowed_hosts' => false,
                ],
                'session' => [
                    'store' => $store,
                    'cache_pool' => (string) $env('SOLIDINVOICE_MCP_SESSION_CACHE_POOL', 'cache.mcp.sessions'),
                    'prefix' => (string) $env('SOLIDINVOICE_MCP_SESSION_PREFIX', 'mcp-'),
                    'ttl' => (int) $env('SOLIDINVOICE_MCP_SESSION_TTL', 3600),
                    // Serializes concurrent requests of one session. The file store locks with
                    // flock() next to its sessions; any other store uses the app's lock.factory
                    // (SOLIDINVOICE_LOCK_DSN), so multi-node deployments share one lock store.
                    'lock' => $store === 'file' ? ['enabled' => true] : ['enabled' => true, 'factory' => 'lock.factory'],
                ],
                'registry' => '*',
            ],
        ],
    ],
]);
