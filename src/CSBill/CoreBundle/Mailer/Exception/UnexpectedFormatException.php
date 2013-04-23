<?php

namespace CSBill\CoreBundle\Mailer\Exception;

use UnexpectedValueException;

class UnexpectedFormatException extends UnexpectedValueException
{
    public function __construct($format)
    {
        $message = sprintf('Invalid email format "%s" given', $format);
        parent::__construct($message);
    }
}