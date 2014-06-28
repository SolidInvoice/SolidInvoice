<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use CSBill\CoreBundle\Entity\Status as BaseStatus;

/**
 * CSBill\PaymentBundle\Entity\Status
 *
 * @ORM\Entity
 */
class Status extends BaseStatus
{
    const STATUS_UNKNOWN = 'unknown';

    const STATUS_FAILED = 'failed';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_EXPIRED = 'expired';

    const STATUS_SUCCESS = 'success';

    const STATUS_PENDING = 'pending';

    const STATUS_CANCELED = 'canceled';

    const STATUS_NEW = 'new';

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="status", fetch="EXTRA_LAZY")
     */
    private $payments;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PaymentMethod", mappedBy="defaultStatus", fetch="EXTRA_LAZY")
     */
    private $paymentMethods;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->paymentMethods = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @return ArrayCollection
     */
    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }
}
