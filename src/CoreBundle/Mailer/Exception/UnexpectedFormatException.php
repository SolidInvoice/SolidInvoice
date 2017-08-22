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

namespace SolidInvoice\CoreBundle\Mailer\Exception;

use UnexpectedValueException;

class UnexpectedFormatException extends UnexpectedValueException
{
    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        $message = sprintf('Invalid email format "%s" given', $format);
        parent::__construct($message, 0);
    }
}
