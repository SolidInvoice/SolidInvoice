<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CronBundle;

class Runner
{
    /**
     * @var CommandInterface[]
     */
    protected $commands = array();

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