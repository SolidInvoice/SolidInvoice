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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\DependencyInjection\GridConfigurationTest
 */
class GridConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('datagrid');
        $node = $treeBuilder->getRootNode();

        $node
            ->useAttributeAsKey('name', true)
            ->arrayPrototype()
                ->children()
                    ->scalarNode('icon')
                        ->defaultValue('')
                    ->end()
                    ->scalarNode('title')
                        ->defaultValue('')
                    ->end()
                    ->arrayNode('properties')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('sortable')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('paginate')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('route')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('source')
                        ->isRequired()
                        ->children()
                            ->scalarNode('repository')
                                ->cannotBeEmpty()
                                ->isRequired()
                            ->end()
                            ->scalarNode('method')
                                ->cannotBeEmpty()
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('columns')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('label')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->booleanNode('editable')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('cell')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('formatter')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('search')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('fields')
                                ->isRequired()
                                ->requiresAtLEastOneElement()
                                ->scalarPrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('actions')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('label')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('icon')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('confirm')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('className')
                                    ->defaultValue('default')
                                ->end()
                                ->scalarNode('action')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('line_actions')
                        ->arrayPrototype()
                            ->validate()
                                ->ifTrue(function ($data) {
                                    return empty($data['icon']) && empty($data['label']);
                                })
                                ->thenInvalid('At least one of "icon" or "label" needs to be set')
                            ->end()
                            ->children()
                                ->scalarNode('label')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('icon')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('route')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('route_params')
                                    ->useAttributeAsKey('name')
                                    ->scalarPrototype()
                                    ->end()
                                ->end()
                                ->arrayNode('conditions')
                                    ->scalarPrototype()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('filters')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('type')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->booleanNode('multiple')
                                    ->defaultFalse()
                                ->end()
                                ->arrayNode('source')
                                    ->children()
                                        ->scalarNode('repository')
                                            ->cannotBeEmpty()
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('method')
                                            ->cannotBeEmpty()
                                            ->isRequired()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
