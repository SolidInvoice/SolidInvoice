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

namespace SolidInvoice\InvoiceBundle\Exception;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $transition)
    {
        $message = 'invoice.transition.exception.'.$transition;

        parent::__construct($message);
    }
}
