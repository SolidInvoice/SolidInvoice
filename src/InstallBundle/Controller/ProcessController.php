<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Controller;

use Sylius\Bundle\FlowBundle\Controller\ProcessController as BaseController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

class ProcessController extends BaseController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function displayAction(Request $request, $scenarioAlias, $stepName)
    {
	$twig = $this->container->get('twig');

	$twig->addGlobal('context', $this->processContext);

	return parent::displayAction($request, $scenarioAlias, $stepName);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(Request $request, $scenarioAlias, $stepName)
    {
	$twig = $this->container->get('twig');

	$twig->addGlobal('context', $this->processContext);

	return parent::forwardAction($request, $scenarioAlias, $stepName);
    }
}
