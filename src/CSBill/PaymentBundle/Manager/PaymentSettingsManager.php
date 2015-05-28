<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

class PaymentSettingsManager
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    protected $repository;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getRepository('CSBillPaymentBundle:PaymentMethod');
    }

    /**
     * @param string $paymentMethod
     *
     * @return array
     */
    public function get($paymentMethod)
    {
        return $this->repository->getSettingsForMethodArray($paymentMethod);
    }
}
