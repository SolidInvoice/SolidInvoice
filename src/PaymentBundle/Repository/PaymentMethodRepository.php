<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class PaymentMethodRepository extends EntityRepository
{
    /**
     * @param string $gatewayName
     *
     * @return array
     */
    public function getSettingsForMethodArray(string $gatewayName): array
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('pm.config')
            ->where('pm.gatewayName = :gatewayName')
            ->setParameter('gatewayName', $gatewayName);

        try {
            $settings = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return [];
        }

        return $settings['settings'];
    }

    /**
     * Get the total number of payment gateways configured.
     *
     * @param bool $includeInternal
     *
     * @return int
     */
    public function getTotalMethodsConfigured(bool $includeInternal = true): int
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('COUNT(pm.id)')
            ->where('pm.enabled = 1');

        if (true !== $includeInternal) {
            $expr = $queryBuilder->expr();

            $queryBuilder->andWhere(
                $expr->orX(
                    $expr->neq('pm.internal', 1),
                    $expr->isNull('pm.internal')
                )
            );
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
