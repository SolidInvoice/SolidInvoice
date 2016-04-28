<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class GridConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
	$treeBuilder = new TreeBuilder();
	$node = $treeBuilder->root('datagrid');

	$node
	    ->useAttributeAsKey('name', true)
	    ->prototype('array')
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
			->end()
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
		    ->arrayNode('columns')
			->prototype('array')
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
				->prototype('scalar')
				->end()
			    ->end()
			->end()
		    ->end()
		    ->arrayNode('actions')
			->prototype('array')
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
			->prototype('array')
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
				    ->prototype('scalar')
				    ->end()
				->end()
				->arrayNode('conditions')
				    ->prototype('scalar')
				    ->end()
				->end()
			    ->end()
			->end()
		    ->end()
		    ->arrayNode('filters')
			->prototype('array')
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
