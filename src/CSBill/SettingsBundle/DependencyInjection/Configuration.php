<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
                                                        ->scalarNode('key')->isRequired()->end()
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
                                            ->scalarNode('key')->isRequired()->end()
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
                                ->scalarNode('key')->isRequired()->end()
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
            ;

        return $treeBuilder;
    }
}
