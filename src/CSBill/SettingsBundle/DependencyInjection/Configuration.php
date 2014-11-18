<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('csbill_settings');

        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->arrayNode('children')
                        ->prototype('array')
                            ->children()
                                ->arrayNode('children')
                                    ->prototype('array')
                                        ->children()
                                            ->arrayNode('settings')
                                                ->prototype('array')
                                                    ->children()
                                                        ->scalarNode('name')->isRequired()->end()
                                                        ->scalarNode('value')->end()
                                                        ->scalarNode('type')->end()
                                                        ->arrayNode('options')
                                                            ->prototype('scalar')->end()
                                                        ->end()
                                                        ->scalarNode('description')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('settings')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->isRequired()->end()
                                            ->scalarNode('value')->end()
                                            ->scalarNode('type')->end()
                                            ->arrayNode('options')
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->scalarNode('description')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('settings')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->scalarNode('value')->end()
                                ->scalarNode('type')->end()
                                ->arrayNode('options')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->scalarNode('description')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
