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

namespace SolidInvoice\DataGridBundle\Exception;

use Exception;
use Throwable;

class InvalidGridException extends Exception
{
    public function __construct(string $grid, ?Throwable $previous = null)
    {
        $message = sprintf('The grid "%s" does not exist.', $grid);

        parent::__construct($message, 0, $previous);
    }
}
