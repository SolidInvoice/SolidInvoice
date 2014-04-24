<?php

namespace CSBill\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cs_bill_payment');

        $rootNode
            ->children()
                ->arrayNode('methods')
                    ->isRequired()
                    ->prototype('array')
                        ->canBeDisabled()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) { return array('context'=> $v); })
                        ->end()
                        ->children()
                            ->scalarNode('context')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('settings')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->enumNode('type')
                                            ->values(array('text', 'password', 'checkbox', 'radio', 'dropdown'))
                                            ->defaultValue('text')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
