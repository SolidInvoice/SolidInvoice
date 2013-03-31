<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use CSBill\CoreBundle\DependencyInjection\Compiler;

class CSBillCoreBundle extends Bundle
{
    /**
     * Extends the CSCoreBundle, so we can override some of the features specific to this application
     */
    public function getParent()
    {
        return 'CSCoreBundle';
    }

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
