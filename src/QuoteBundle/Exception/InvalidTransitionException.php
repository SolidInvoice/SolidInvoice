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

namespace CSBill\QuoteBundle\Exception;

class InvalidTransitionException extends \Exception
{
    /**
     * @param string $transition
     */
    public function __construct(string $transition)
    {
        $message = 'quote.transition.exception.'.$transition;

        parent::__construct($message);
    }
}
