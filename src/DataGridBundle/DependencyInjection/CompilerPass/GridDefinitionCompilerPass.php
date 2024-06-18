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

namespace SolidInvoice\DataGridBundle\DependencyInjection\CompilerPass;

use SolidInvoice\DataGridBundle\Filter\ChainFilter;
use SolidInvoice\DataGridBundle\Filter\PaginateFilter;
use SolidInvoice\DataGridBundle\Filter\SearchFilter;
use SolidInvoice\DataGridBundle\Filter\SortFilter;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\Repository\GridRepository;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GridDefinitionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(GridRepository::class);

        foreach ($container->getParameter('grid.definitions') as $name => $config) {
            $this->setGridDefinition($definition, $name, $config);
        }
    }

    /**
     * @param array<string, string|list<string|bool>> $gridConfig
     */
    private function setGridDefinition(Definition $gridService, string $name, array $gridConfig): void
    {
        $gridDefinition = new Definition(Grid::class);

        return;

        $gridConfig['name'] = $name;
        $gridDefinition->addArgument($this->getGridSource($gridConfig['source']));
        $gridDefinition->addArgument($this->getFilterService($gridConfig));
        $gridDefinition->addArgument($gridConfig);
        $gridDefinition->addArgument(new Reference(MoneyFormatter::class));

        $gridService->addMethodCall('addGrid', [$name, $gridDefinition]);
    }

    private function getGridSource(array $arguments): Definition
    {
        array_unshift($arguments, new Reference('doctrine'));

        return new Definition(ORMSource::class, array_values($arguments));
    }

    private function getFilterService(array &$gridData): Definition
    {
        $definition = new Definition(ChainFilter::class);

        if (true === ($gridData['properties']['sortable'] ?? true)) {
            $sortFilter = new Definition(SortFilter::class);
            $definition->addMethodCall('addFilter', [$sortFilter]);
        }

        if (true === ($gridData['properties']['paginate'] ?? true)) {
            $paginateFilter = new Definition(PaginateFilter::class);
            $definition->addMethodCall('addFilter', [$paginateFilter]);
        }

        if (! empty($gridData['search']['fields'])) {
            $searchFilter = new Definition(
                SearchFilter::class,
                [$gridData['search']['fields']]
            );
            $definition->addMethodCall('addFilter', [$searchFilter]);
            $gridData['properties']['search'] = true;
        }

        return $definition;
    }
}
