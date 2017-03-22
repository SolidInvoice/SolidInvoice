<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CronBundle\Tests;

use CSBill\CronBundle\Runner;
use Mockery as M;

class RunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testRun()
    {
        $cron = new Runner();

        $command = M::mock('CSBill\CronBundle\CommandInterface');

        $command->shouldReceive('isDue')
            ->once()
            ->andReturn(true);

        $command->shouldReceive('process')
            ->once();

        $cron->addCommand($command);

        $cron->run();
    }

    public function testRunNoCommands()
    {
        $cron = new Runner();

        $command = M::mock('CSBill\CronBundle\CommandInterface');

        $command->shouldReceive('isDue')
            ->once()
            ->andReturn(false);

        $command->shouldReceive('process')
            ->never();

        $cron->addCommand($command);

        $cron->run();
    }
}
