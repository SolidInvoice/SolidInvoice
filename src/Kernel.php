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

namespace SolidInvoice;

use Doctrine\DBAL\Types\Type;
use Override;
use SolidInvoice\CoreBundle\Doctrine\Type\ArrayType;
use SolidInvoice\CoreBundle\Doctrine\Type\JsonArrayType;
use SolidInvoice\CoreBundle\Doctrine\Type\ObjectType;
use SolidWorx\Platform\PlatformBundle\Kernel as BaseKernel;
use SolidWorx\Platform\SaasBundle\SolidWorxPlatformSaasBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use function preg_replace;

class Kernel extends BaseKernel
{
    #[Override]
    public function boot(): void
    {
        parent::boot();

        if (! Type::hasType('json_array')) {
            // Only here for BC to ensure migrations work. Remove in next minor release.
            Type::addType('json_array', JsonArrayType::class);
        }

        if (! Type::hasType(ArrayType::NAME)) {
            // BC for the "array" type removed in DBAL 4 (used by historical migrations and entities).
            Type::addType(ArrayType::NAME, ArrayType::class);
        }

        if (! Type::hasType(ObjectType::NAME)) {
            // BC for the "object" type removed in DBAL 4 (used by historical migrations and Payum's Token mapping).
            Type::addType(ObjectType::NAME, ObjectType::class);
        }
    }

    #[Override]
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    #[Override]
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        parent::configureContainer($container, $loader, $builder);

        $bundles = $this->getBundles();

        if (($bundles['SolidWorxPlatformSaasBundle'] ?? null) instanceof SolidWorxPlatformSaasBundle) {
            $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());
            $container->import($configDir . '/{packages}/saas/*.{php,yaml}');
        }
    }

    #[Override]
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        parent::configureRoutes($routes);

        $bundles = $this->getBundles();

        if (($bundles['SolidWorxPlatformSaasBundle'] ?? null) instanceof SolidWorxPlatformSaasBundle) {
            $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());
            $routes->import($configDir . '/{routes}/saas/*.{php,yaml}');

        }
    }

    private function getConfigDir(): string
    {
        return $this->getProjectDir() . '/config';
    }
}
