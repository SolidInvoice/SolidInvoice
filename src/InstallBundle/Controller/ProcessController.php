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

namespace SolidInvoice\InstallBundle\Controller;

use Sylius\Bundle\FlowBundle\Controller\ProcessController as BaseController;
use Symfony\Component\HttpFoundation\Request;

class ProcessController extends BaseController
{
    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @param \Twig\Environment $twig
     *
     * @required
     */
    public function setTwig(\Twig\Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function displayAction(Request $request, $scenarioAlias, $stepName)
    {
        $this->twig->addGlobal('context', $this->processContext);

        return parent::displayAction($request, $scenarioAlias, $stepName);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(Request $request, $scenarioAlias, $stepName)
    {
        $this->twig->addGlobal('context', $this->processContext);

        return parent::forwardAction($request, $scenarioAlias, $stepName);
    }
}
