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
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
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
                                ->scalarNode('description')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;

        $rootNode
            ->children()
                ->arrayNode('quote')
                    ->children()
                        ->arrayNode('settings')
                            ->requiresAtLeastOneElement()
                            ->prototype('array')
                                ->children()
                                     //->useAttributeAsKey('key')
                                    ->scalarNode('key')->isRequired()->end()
                                    ->scalarNode('value')->end()
                                    ->scalarNode('description')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        return $treeBuilder;
    }
}
