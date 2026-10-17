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
use SolidInvoice\AppMode;
use SolidInvoice\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A SaaS kernel whose One Tap CORS rule allows the test marketing origin. That allow-list is read
 * while the container is built (config/packages/saas/nelmio_cors.php), and Foundry boots the
 * kernel before any test hook runs, so it is set here, and only for the build. The cache dir is
 * separate so the plain SaaS container is never built with it.
 */
final class SaasTestKernel extends Kernel
{
    public const string MARKETING_ORIGIN = 'https://marketing.example.test';

    private const string ORIGINS_ENV = 'SOLIDINVOICE_ONE_TAP_ALLOWED_ORIGINS';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct(AppMode::SAAS, $environment, $debug);
    }

    #[Override]
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment . '/saas-onetap';
    }

    #[Override]
    protected function buildContainer(): ContainerBuilder
    {
        $previous = [$_SERVER[self::ORIGINS_ENV] ?? null, $_ENV[self::ORIGINS_ENV] ?? null];
        $_SERVER[self::ORIGINS_ENV] = $_ENV[self::ORIGINS_ENV] = self::MARKETING_ORIGIN;

        try {
            return parent::buildContainer();
        } finally {
            [$server, $env] = $previous;

            if ($server === null) {
                unset($_SERVER[self::ORIGINS_ENV]);
            } else {
                $_SERVER[self::ORIGINS_ENV] = $server;
            }

            if ($env === null) {
                unset($_ENV[self::ORIGINS_ENV]);
            } else {
                $_ENV[self::ORIGINS_ENV] = $env;
            }
        }
    }
}
