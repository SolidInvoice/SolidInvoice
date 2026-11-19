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

// DNS rebinding protection hosts for the MCP HTTP transport. A custom array replaces
// the SDK's own default entirely, so the localhost variants from that default are
// repeated here, plus this install's real host (SOLIDINVOICE_APPLICATION_URL) and any
// extra hosts from the comma-separated SOLIDINVOICE_MCP_ALLOWED_HOSTS.
$allowedHosts = ['localhost', '127.0.0.1', '[::1]'];

$applicationHost = parse_url((string) $env('SOLIDINVOICE_APPLICATION_URL', ''), PHP_URL_HOST);

if (is_string($applicationHost) && '' !== $applicationHost) {
    $allowedHosts[] = $applicationHost;
}

foreach (explode(',', (string) $env('SOLIDINVOICE_MCP_ALLOWED_HOSTS', '')) as $host) {
    $host = trim($host);

    if ('' !== $host) {
        $allowedHosts[] = $host;
    }
}

$allowedHosts = array_values(array_unique($allowedHosts));

return App::config([
    'mcp' => [
        'app' => SolidInvoiceCoreBundle::APP_NAME,
        'version' => SolidInvoiceCoreBundle::VERSION,
        'description' => 'MCP server for ' . SolidInvoiceCoreBundle::APP_NAME,
        'client_transports' => [
            'stdio' => false,
            'http' => true,
        ],
        'http' => [
            'path' => '/_mcp',
            'allowed_hosts' => $allowedHosts,
            'session' => [
                'store' => (string) $env('SOLIDINVOICE_MCP_SESSION_STORE', 'file'),
                'cache_pool' => (string) $env('SOLIDINVOICE_MCP_SESSION_CACHE_POOL', 'cache.mcp.sessions'),
                'prefix' => (string) $env('SOLIDINVOICE_MCP_SESSION_PREFIX', 'mcp-'),
                'ttl' => (int) $env('SOLIDINVOICE_MCP_SESSION_TTL', 3600),
            ],
        ],
    ],
]);
