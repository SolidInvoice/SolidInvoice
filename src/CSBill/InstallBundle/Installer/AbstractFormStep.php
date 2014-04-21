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

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractFormStep extends AbstractStep implements StepFormInterface
{
    protected $form;

    /**
     * @return FormInterface
     */
    public function buildForm()
    {
        if (null === $this->form) {
            $factory = $this->container->get('form.factory');

            $this->form = $factory->create($this->getForm(), array(), $this->getFormData());
        }

        return $this->form;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormData()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handleRequest(Request $request)
    {
        $form = $this->buildForm();
        $form->handleRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function isValid()
    {
        return $this->buildForm()->isValid();
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getForm();
}
