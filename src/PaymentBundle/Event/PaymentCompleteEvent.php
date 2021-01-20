<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Event;

use SolidInvoice\CoreBundle\Event\ResponseEvent;
use SolidInvoice\PaymentBundle\Entity\Payment;

class PaymentCompleteEvent extends ResponseEvent
{
    /**
     * @var Payment
     */
    protected $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
