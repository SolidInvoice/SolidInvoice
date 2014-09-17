<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use CSBill\CoreBundle\DependencyInjection\Compiler;

class CSBillCoreBundle extends Bundle
{
    const VERSION = '0.3.0';

    /**
     * (non-phpdoc)
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\MenuCompilerPass());
        $container->addCompilerPass(new Compiler\FormCompilerPass());
    }
}
