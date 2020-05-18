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

namespace SolidInvoice\CronBundle\Command;

use SolidInvoice\CronBundle\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronRunCommand extends Command
{
    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var string|null
     */
    private $installed;

    public function __construct(Runner $runner, ?string $installed = null)
    {
        $this->runner = $runner;
        $this->installed = $installed;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cron:run')
            ->setDescription('Runs the cron commands');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->installed) {
            return 0;
        }

        $this->runner->run();

        return 0;
    }
}
