<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class MenuCompilerPass implements CompilerPassInterface
{
	/**
	 * (non-phpdoc)
	 *
	 * @param ContainerBuilder $container
	 */
	public function process(ContainerBuilder $container)
	{
		if (!$container->hasDefinition('cs_bill_core.menu.provider')) {
			return;
		}

		$definition = $container->getDefinition('cs_bill_core.menu.provider');

		$taggedServices = $container->findTaggedServiceIds('cs_core.menu');

		foreach ($taggedServices as $id => $tagAttributes) {
			foreach ($tagAttributes as $attributes) {
				$definition->addMethodCall(
						'addBuilder',
						array(new Reference($id), $attributes["menu"], $attributes["method"])
				);
			}
		}
	}
}