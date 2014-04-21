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
