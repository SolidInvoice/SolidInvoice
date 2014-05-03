<?php

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
