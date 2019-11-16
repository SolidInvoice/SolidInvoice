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

namespace SolidInvoice\InstallBundle\Process;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class InstallScenario implements ProcessScenarioInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ProcessBuilderInterface $builder)
    {
        $builder->add('system_check', new Step\SystemRequirementsStep());
        $builder->add('config', new Step\ConfigStep());
        $builder->add('process', new Step\InstallStep());
        $builder->add('setup', new Step\SetupStep($this->encoderFactory));
        $builder->add('finish', new Step\FinishStep());

        $builder->setRedirect('_home');
    }
}
