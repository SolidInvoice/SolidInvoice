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
    public function __construct(string $environment, bool $debug)
    {
        foreach ([
            'SOLIDINVOICE_PLATFORM' => 'saas',
            'SOLIDINVOICE_LEMON_SQUEEZY_API_KEY' => 'test-api-key',
            'SOLIDINVOICE_LEMON_SQUEEZY_STORE_ID' => 'test-store',
            'SOLIDINVOICE_LEMON_SQUEEZY_WEBHOOK_SECRET' => 'test-secret',
        ] as $name => $value) {
            $_ENV[$name] = $_SERVER[$name] = $value;
        }

        parent::__construct($environment, $debug);
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
}
