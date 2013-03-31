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
