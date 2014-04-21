<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InstallBundle\Exception\ApplicationInstalledException;
use Symfony\Component\HttpFoundation\Request;

class InstallController extends BaseController
{
    public function indexAction(Request $request)
    {
        $installer = $this->get('csbill.installer');

        if ($installer->isInstalled()) {
            throw new ApplicationInstalledException();
        }

        $step = $installer->getCurrentStep();

        if (false === $installer->previousStepComplete()) {
            $installer->setSession('current_step', $installer->currentStepIndex - 1);

            return $this->redirect($this->generateUrl($installer::INSTALLER_ROUTE));
        }

        $step->handleRequest($request);

        if ($step->isValid()) {
            $step->process();

            if ($installer->isFinal()) {
                return $this->redirect($this->generateUrl($installer::INSTALLER_SUCCESS_ROUTE));
            }

            $installer->advanceStep();

            return $this->redirect($this->generateUrl($installer::INSTALLER_ROUTE));
        }

        return $this->render(
            'CSBillInstallBundle:Install:index.html.twig',
            array(
                'step' => $step,
                'step_label' => $installer->currentStep['label'],
                'installer' => $installer
            )
        );
    }

    public function stepAction($step)
    {
        $installer = $this->get('csbill.installer');

        $installer->setSession('current_step', $step);

        return $this->redirect($this->generateUrl($installer::INSTALLER_ROUTE));
    }

    public function restartAction()
    {
        $installer = $this->get('csbill.installer');

        $installer->restart();

        return $this->redirect($this->generateUrl($installer::INSTALLER_ROUTE));
    }

    public function successAction()
    {
        return $this->render('CSBillInstallBundle:Install:success.html.twig');
    }
}
