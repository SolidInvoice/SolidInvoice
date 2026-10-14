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

namespace SolidInvoice\Test;

use Override;
use SolidInvoice\AppMode;
use SolidWorx\Platform\PlatformBundle\Kernel as PlatformKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function str_contains;
use function str_replace;

/**
 * A hosted kernel with email verification switched on.
 *
 * Verification is decided while platform.yaml is parsed, and when it is off the platform drops
 * the verification bundle, routes and controllers altogether, so it cannot be turned on from a
 * test container. This kernel defaults it on instead, and keeps its own cache so neither its
 * container nor its parsed platform config is shared with the kernels that have it off.
 *
 * The database is {@see SaasKernel}'s, though: verification adds no tables, tests/bootstrap.php
 * only builds the schemas of the two standard kernels, and this kernel already shares the
 * SaaS kernel's static DAMA connection key (config/packages/saas/dama_doctrine_test.yaml).
 */
final class EmailVerificationKernel extends \SolidInvoice\Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct(AppMode::SAAS, $environment, $debug);
    }

    #[Override]
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment . '/email_verification';
    }

    #[Override]
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        // The test database URL lives under %kernel.cache_dir%, which is this kernel's own.
        $url = (string) ($_SERVER['SOLIDINVOICE_DATABASE_URL'] ?? $_ENV['SOLIDINVOICE_DATABASE_URL'] ?? '');

        if (str_contains($url, '%kernel.cache_dir%')) {
            $saasCacheDir = new SaasKernel($this->environment, $this->debug)->getCacheDir();

            $container->extension('doctrine', [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'url' => str_replace('%kernel.cache_dir%', $saasCacheDir, $url),
                        ],
                    ],
                ],
            ]);
        }
    }

    #[Override]
    public static function resolveEnvValues(mixed $value, ContainerBuilder $container = new ContainerBuilder()): mixed
    {
        // Skips \SolidInvoice\Kernel, whose override pins the default to off.
        $container->setParameter('email_verification_enabled', true);

        return PlatformKernel::resolveEnvValues($value, $container);
    }
}
