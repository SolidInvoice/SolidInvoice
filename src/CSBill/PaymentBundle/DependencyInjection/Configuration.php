<?php

namespace CSBill\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    protected $availableFieldTypes = array(
        'text',
        'password',
        'checkbox',
        'radio',
        'choice',
        'textarea',
    );

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
                            ->then(function ($v) {
                                return array('context' => $v);
                            })
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
                                            ->values($this->availableFieldTypes)
                                            ->defaultValue('text')
                                        ->end()
                                        ->arrayNode('options')
                                            ->prototype('scalar')->end()
                                            ->defaultValue(array())
                                        ->end()
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
