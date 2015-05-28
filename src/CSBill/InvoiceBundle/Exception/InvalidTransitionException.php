<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Exception;

class InvalidTransitionException extends \Exception
{
    /**
     * @param string $transition
     */
    public function __construct($transition)
    {
        $message = 'invoice.transition.exception.'.$transition;

        parent::__construct($message);
    }
}
