<?php

namespace CSBill\PaymentBundle\Event;

use CSBill\PaymentBundle\Entity\Payment;
use Symfony\Component\EventDispatcher\Event;

class PaymentCompleteEvent extends Event
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
