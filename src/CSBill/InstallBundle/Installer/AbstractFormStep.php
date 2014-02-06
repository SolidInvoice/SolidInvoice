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
