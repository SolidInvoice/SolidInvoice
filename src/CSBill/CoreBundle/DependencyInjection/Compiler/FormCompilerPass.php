<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class FormCompilerPass implements CompilerPassInterface
{
    /**
     * (non-phpdoc)
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('form.type.money')) {
            return;
        }

        $definition = $container->getDefinition('form.type.money');
        $definition->setClass('CSBill\CoreBundle\Form\Type\Money');

        $definition->addArgument($container->getDefinition('csbill_core.currency'));
    }
}
