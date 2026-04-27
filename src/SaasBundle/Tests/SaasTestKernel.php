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

namespace SolidInvoice\SaasBundle\Tests;

use Override;
use SolidInvoice\Kernel;

final class SaasTestKernel extends Kernel
{
    private const array ENV_OVERRIDES = [
        'SOLIDINVOICE_PLATFORM' => 'saas',
        'SOLIDINVOICE_LEMON_SQUEEZY_API_KEY' => 'test-api-key',
        'SOLIDINVOICE_LEMON_SQUEEZY_STORE_ID' => 'test-store',
        'SOLIDINVOICE_LEMON_SQUEEZY_WEBHOOK_SECRET' => 'test-secret',
    ];

    /**
     * @var array<string, array{env: ?string, server: ?string}>
     */
    private array $previousEnv = [];

    public function __construct(string $environment, bool $debug)
    {
        foreach (self::ENV_OVERRIDES as $name => $value) {
            $this->previousEnv[$name] = [
                'env' => array_key_exists($name, $_ENV) ? (string) $_ENV[$name] : null,
                'server' => array_key_exists($name, $_SERVER) ? (string) $_SERVER[$name] : null,
            ];
            $_ENV[$name] = $_SERVER[$name] = $value;
        }

        parent::__construct($environment, $debug);
    }

    #[Override]
    public function shutdown(): void
    {
        parent::shutdown();

        // Restore the env vars we overrode in the constructor so that other tests
        // (which share the same PHP process) don't see stale `SOLIDINVOICE_PLATFORM=saas`
        // and incorrectly enable SaaS-only behaviour.
        foreach ($this->previousEnv as $name => $previous) {
            $this->restoreEnv($name, $previous['env'], $previous['server']);
        }

        $this->previousEnv = [];
    }

    #[Override]
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
    }

    #[Override]
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/saas_' . $this->environment;
    }

    #[Override]
    public function getBuildDir(): string
    {
        return $this->getCacheDir();
    }

    #[Override]
    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log/saas_' . $this->environment;
    }

    private function restoreEnv(string $name, ?string $envValue, ?string $serverValue): void
    {
        if ($envValue === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $envValue;
        }

        if ($serverValue === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $serverValue;
        }
    }
}
