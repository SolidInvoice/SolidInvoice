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

interface StepFormInterface extends StepInterface
{
    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    public function buildForm();

    /**
     * @return \Symfony\Component\Form\AbstractType
     */
    public function getForm();

    /**
     * Returns default data that should be used in the form
     *
     * @return array
     */
    public function getFormData();
}
