<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
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
    protected static $defaultName = 'cron:run';

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

    protected function configure(): void
    {
        $this->setDescription('Runs the cron commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->installed) {
            return 0;
        }

        $this->runner->run();

        return 0;
    }
}
