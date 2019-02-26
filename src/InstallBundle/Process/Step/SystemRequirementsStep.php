<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\AbstractControllerStep;
use Symfony\Component\HttpFoundation\Response;

class SystemRequirementsStep extends AbstractControllerStep
{
    /**
     * @param ProcessContextInterface $context
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function displayAction(ProcessContextInterface $context): Response
    {
        if ($this->container->getParameter('installed')) {
            $flashBag = $this->get('session')->getFlashBag();

            $flashBag->add('error', $this->get('translator')->trans('installation.already_installed'));

            return $this->redirectToRoute('_home');
        }

        return $this->render(
            '@SolidInvoiceInstall/Flow/system_check.html.twig',
            [
                'requirements' => new \AppRequirements(),
            ]
        );
    }

    /**
     * @param ProcessContextInterface $context
     *
     * @return \Sylius\Bundle\FlowBundle\Process\Step\ActionResult|\Symfony\Component\HttpFoundation\Response
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $requirements = new \AppRequirements();

        if (0 !== count($requirements->getFailedRequirements())) {
            return $this->redirectToRoute('_install_flow');
        }

        return $this->complete();
    }
}
