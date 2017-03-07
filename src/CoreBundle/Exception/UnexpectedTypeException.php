<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Exception;

class UnexpectedTypeException extends \InvalidArgumentException
{
    /**
     * @param string $value
     * @param string $expectedType
     */
    public function __construct($value, $expectedType)
    {
	parent::__construct(
	    sprintf(
		'Expected argument of type "%s", "%s" given',
		$expectedType,
		is_object($value) ? get_class($value) : gettype($value)
	    )
	);
    }
}
