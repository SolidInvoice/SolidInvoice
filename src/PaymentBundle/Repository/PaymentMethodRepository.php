<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NoResultException;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;

class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

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
     */
    public function getTotalMethodsConfigured(bool $includeInternal = true): int
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('COUNT(pm.id)')
            ->where('pm.enabled = 1');

        if (!$includeInternal) {
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
