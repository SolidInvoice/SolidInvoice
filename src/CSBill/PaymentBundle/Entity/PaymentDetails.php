<?php

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
}
