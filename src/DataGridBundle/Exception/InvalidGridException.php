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

namespace SolidInvoice\DataGridBundle\Exception;

use Exception;

class InvalidGridException extends Exception
{
    public function __construct(string $grid)
    {
        $message = sprintf('The grid "%s" does not exist.', $grid);

        parent::__construct($message);
    }
}
