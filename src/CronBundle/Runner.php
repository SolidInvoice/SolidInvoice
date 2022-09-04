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

namespace SolidInvoice\CronBundle;

/**
 * @see \SolidInvoice\CronBundle\Tests\RunnerTest
 */
class Runner
{
    /**
     * @var CommandInterface[]
     */
    protected $commands = [];

    public function addCommand(CommandInterface $command)
    {
        $this->commands[] = $command;
    }

    public function run()
    {
        foreach ($this->commands as $command) {
            if ($command->isDue()) {
                $command->process();
            }
        }
    }
}
