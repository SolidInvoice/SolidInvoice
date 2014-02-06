<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface StepViewInterface extends ContainerAwareInterface
{
    /**
     * @return \Symfony\Component\Form\AbstractType
     */
    public function getTemplate();

    /**
     * Returns an array of variables that will be passed to the view
     */
    public function getViewVars();
}
