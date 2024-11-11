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

namespace SolidInvoice\DataGridBundle\DependencyInjection;

use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SolidInvoiceDataGridExtension extends Extension
{
    /**
     * @param list<mixed> $configs
     * @throws LoaderLoadException
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->import('services/*.php');

        $grids = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('grid.definitions', $grids);
    }

    /**
     * @param list<mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): GridConfiguration
    {
        return new GridConfiguration();
    }

    public function getAlias(): string
    {
        return 'datagrid';
    }
}
