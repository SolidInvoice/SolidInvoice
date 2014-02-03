<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\InstallBundle\Exception\ApplicationInstalledException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class InstallController extends Controller
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
