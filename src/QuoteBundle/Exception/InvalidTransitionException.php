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

namespace SolidInvoice\QuoteBundle\Exception;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $transition)
    {
        $message = 'quote.transition.exception.' . $transition;

        parent::__construct($message);
    }
}
