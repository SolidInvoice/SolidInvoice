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

class FinishStep extends AbstractControllerStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $rootDir = $this->container->getParameter('kernel.root_dir');

        return $this->render('@SolidInvoiceInstall/Flow/finish.html.twig', ['rootDir' => $rootDir]);
    }
}
