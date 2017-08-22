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

namespace SolidInvoice\CronBundle;

class Runner
{
    /**
     * @var CommandInterface[]
     */
    protected $commands = [];

    /**
     * @param CommandInterface $command
     */
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
