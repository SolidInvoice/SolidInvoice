<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Process;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class InstallScenario implements ContainerAwareInterface, ProcessScenarioInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function build(ProcessBuilderInterface $builder)
    {
	$builder->add('system_check', new Step\SystemRequirementsStep());
	$builder->add('config', new Step\ConfigStep());
	$builder->add('process', new Step\InstallStep());
	$builder->add('setup', new Step\SetupStep());
	$builder->add('finish', new Step\FinishStep());

	$builder->setRedirect('_home');
    }
}
