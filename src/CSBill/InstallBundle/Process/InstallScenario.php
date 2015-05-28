<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\InstallBundle\Process;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class InstallScenario extends ContainerAware implements ProcessScenarioInterface
{
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
