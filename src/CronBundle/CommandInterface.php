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

namespace CSBill\CronBundle;

interface CommandInterface
{
    /**
     * Checks if command is due to run.
     *
     * @return bool
     */
    public function isDue(): bool;

    /**
     * Process command.
     */
    public function process();
}
