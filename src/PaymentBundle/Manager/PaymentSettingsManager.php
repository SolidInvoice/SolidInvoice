<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
     * @var array
     */
    private $settings = [];

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
    public function get(string $paymentMethod): array
    {
        if (!isset($this->settings[$paymentMethod])) {
            $this->settings[$paymentMethod] = $this->repository->getSettingsForMethodArray($paymentMethod);
        }

        return $this->settings[$paymentMethod];
    }
}
