<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
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
