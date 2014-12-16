<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Twig;

use CSBill\InstallBundle\Installer\Installer;
use CSBill\InstallBundle\Installer\StepFormInterface;
use CSBill\InstallBundle\Installer\StepInterface;
use CSBill\InstallBundle\Installer\StepViewInterface;
use Twig_Environment;
use Twig_Extension;

class InstallExtension extends Twig_Extension
{
    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @var Twig_Environment
     */
    protected $environment;

    /**
     * @param Installer $installer
     */
    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    /**
     * @param Twig_Environment $environment
     */
    public function initRuntime(Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('install_step', array($this, 'getInstallStep'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('is_first_install_step', array($this, 'isFirstInstallStep')),
        );
    }

    /**
     * @param StepInterface $step
     *
     * @return string
     */
    public function getInstallStep(StepInterface $step)
    {
        if ($step instanceof StepFormInterface) {
            $form = $step->buildForm();

            return $this->environment->render(
                'CSBillInstallBundle:Install:form.html.twig',
                array(
                    'form' => $form->createView(),
                )
            );
        }

        if ($step instanceof StepViewInterface) {
            $template = $step->getTemplate();

            return $this->environment->render($template, $step->getViewVars());
        }
    }

    /**
     * Checks if the current installation step is the first step
     *
     * @return bool
     */
    public function isFirstInstallStep()
    {
        return 0 === (int) $this->installer->getSession('current_step', PHP_INT_MAX);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'csbill.twig.install_extension';
    }
}
