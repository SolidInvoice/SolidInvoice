<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\ArrayObject;

/**
 * @ORM\Table(name="payment_details")
 * @ORM\Entity
 */
class PaymentDetails extends ArrayObject
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer $id
     */
    protected $id;

    /**
     * @var Payment
     *
     * @ORM\OneToOne(targetEntity="Payment", mappedBy="details")
     */
    private $payment;

    /**
     * Get the id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     *
     * @return $this
     */
    public function setInvoice(Payment $payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @param array $details
     *
     * @return $this
     */
    public function setDetails(array $details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }
}
