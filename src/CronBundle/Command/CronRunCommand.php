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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see \SolidInvoice\CronBundle\Tests\Command\CronRunCommandTest
 */
#[AsCommand(name: 'cron:run', description: 'Run scheduled tasks')]
class CronRunCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->getApplication()
            ?->find('schedule:run')
            ->run($input, $output);
    }
}
