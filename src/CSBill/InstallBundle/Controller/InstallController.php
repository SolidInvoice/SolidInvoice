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

class InstallController extends Controller
{
    public function indexAction()
    {
        $request = $this->getRequest();

        $installer = $this->get('csbill.installer');

        if ($installer->isInstalled()) {
            throw new ApplicationInstalledException();
        }

        if ($request->isMethod('POST')) {
            $response = $installer->validateStep($request->request->all());

            if ($response instanceof RedirectResponse) {
                return $response;
            }
        }

        $step = $installer->getStep();

        return $this->render('CSBillInstallBundle:Install:index.html.twig', array('step' => $step, 'installer' => $installer));
    }

    public function stepAction($step)
    {
        $installer = $this->get('csbill.installer');

        $installer->setStep($step);

        return $installer->getRedirectResponse();
    }

    public function restartAction()
    {
        $installer = $this->get('csbill.installer');

        $installer->restart();

        return $installer->getRedirectResponse();
    }

    public function successAction()
    {
        return $this->render('CSBillInstallBundle:Install:success.html.twig');
    }
}
