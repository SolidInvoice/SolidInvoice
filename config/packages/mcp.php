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

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Config\McpConfig;

return static function (McpConfig $config): void {
    $config
        ->app(SolidInvoiceCoreBundle::APP_NAME)
        ->version(SolidInvoiceCoreBundle::VERSION)
        ->description('MCP server for ' . SolidInvoiceCoreBundle::APP_NAME);

    $config->clientTransports()
        ->stdio(false)
        ->http(true);

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
    $env = static fn (string $name, string|int $default): string|int => $_ENV[$name] ?? $_SERVER[$name] ?? $default;

    $config->http()
        ->path('/_mcp')
        ->session()
        ->store((string) $env('SOLIDINVOICE_MCP_SESSION_STORE', 'file'))
        ->cachePool((string) $env('SOLIDINVOICE_MCP_SESSION_CACHE_POOL', 'cache.mcp.sessions'))
        ->prefix((string) $env('SOLIDINVOICE_MCP_SESSION_PREFIX', 'mcp-'))
        ->ttl((int) $env('SOLIDINVOICE_MCP_SESSION_TTL', 3600));

    $config->discovery()
        ->scanDirs(['src'])
        ->excludeDirs([
            // Tests/, Test/ and DataFixtures/ are excluded by basename (no
            // slash) so Finder skips them in every bundle — discovering them
            // in prod triggers autoload of dev-only classes (PHPUnit, Foundry's
            // PersistentProxyObjectFactory, doctrine/fixtures Fixture) and
            // crashes the MCP controller.
            'Tests',
            'Test',
            'DataFixtures',
            'src/McpBundle/Entity',
            'src/McpBundle/OAuth',
            'src/McpBundle/Security',
            'src/McpBundle/Action',
        ]);
};
