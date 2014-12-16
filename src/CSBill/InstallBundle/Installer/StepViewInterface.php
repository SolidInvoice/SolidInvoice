<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface StepViewInterface extends ContainerAwareInterface
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * Returns an array of variables that will be passed to the view
     *
     * @return array
     */
    public function getViewVars();
}
