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
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class InstallController extends Controller
{
    /**
     * @Route("/", name="_installer")
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        $installer = $this->get('csbill.installer');

        if ($request->isMethod('POST')) {
            $response = $installer->validateStep($request->request->all());

            if ($response instanceof RedirectResponse) {
                return $response;
            }
        }

        $step = $installer->getStep();

        return $this->render('CSBillInstallBundle:Install:index.html.twig', array('step' => $step, 'installer' => $installer));
    }

    /**
     * @Route("/step/{step}", name="_installer_step")
     */
    public function stepAction($step)
    {
    	$installer = $this->get('csbill.installer');

    	$installer->setStep($step);

    	return $installer->getRedirectResponse();
    }

    /**
     * @Route("/restart", name="_installer_restart")
     */
    public function restartAction()
    {
    	$installer = $this->get('csbill.installer');

    	$installer->restart();

    	return $installer->getRedirectResponse();
    }

    /**
     * @Route("/success", name="_installer_success")
     * @Template()
     */
    public function successAction()
    {
        return array();
    }
}
