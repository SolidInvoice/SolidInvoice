<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
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
