<?php

namespace CSBill\PaymentBundle\Event;

use CSBill\PaymentBundle\Entity\PaymentDetails;
use Symfony\Component\EventDispatcher\Event;

class PaymentCompleteEvent extends Event
{
    /**
     * @var PaymentDetails
     */
    protected $payment;

    /**
     * @param PaymentDetails $payment
     */
    public function __construct(PaymentDetails $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return PaymentDetails
     */
    public function getPayment()
    {
        return $this->payment;
    }
} 