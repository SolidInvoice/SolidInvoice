<?php

namespace CSBill\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PaymentMethod extends EntityRepository
{
    /**
     * @param string $paymentMethod
     *
     * @return array
     */
    public function getSettingsForMethodArray($paymentMethod)
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('pm.settings')
            ->where('pm.paymentMethod = :paymentMethod')
            ->setParameter('paymentMethod', $paymentMethod);

        $settings = $queryBuilder->getQuery()->getSingleResult();

        return $settings['settings'];
    }
} 