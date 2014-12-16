<?php

namespace CSBill\PaymentBundle\Exception;

class NotImplementedException extends \Exception
{
    /**
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message ?: 'Not Implemented', $code, $previous);
    }
}
