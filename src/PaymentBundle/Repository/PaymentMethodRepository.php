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

namespace SolidInvoice\PaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;

/**
 * @extends ServiceEntityRepository<PaymentMethod>
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    /**
     * @return array<string, string>
     * @throws NonUniqueResultException
     */
    public function getSettingsForMethodArray(string $gatewayName): array
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $queryBuilder->select('pm.config')
            ->where('pm.gatewayName = :gatewayName')
            ->setParameter('gatewayName', $gatewayName);

        try {
            $settings = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException) {
            return [];
        }

        return $settings['settings'];
    }

    /**
     * Get the total number of payment gateways configured.
     *
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalMethodsConfigured(bool $includeInternal = true): int
    {
        $queryBuilder = $this->createQueryBuilder('pm');

        $expr = $queryBuilder->expr();

        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $boolType = $platform->convertBooleans(true);

        $queryBuilder->select('COUNT(pm.id)')
            ->where($expr->neq('pm.enabled', $boolType));

        if (! $includeInternal) {
            $queryBuilder->andWhere(
                $expr->orX(
                    $expr->neq('pm.internal', $boolType),
                    $expr->isNull('pm.internal')
                )
            );
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return PaymentMethod[]
     * @throws Exception
     */
    public function getAvailablePaymentMethods(bool $includeInternal): array
    {
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $boolType = $platform->convertBooleans(true);

        $queryBuilder = $this->createQueryBuilder('pm');
        $expression = $queryBuilder->expr();
        $queryBuilder->where($expression->eq('pm.enabled', $boolType));

        if (! $includeInternal) {
            $queryBuilder->andWhere(
                $expression->orX(
                    $expression->neq('pm.internal', $boolType),
                    $expression->isNull('pm.internal')
                )
            );
        }

        $queryBuilder->orderBy($expression->asc('pm.name'));

        return $queryBuilder->getQuery()->getResult();
    }

    public function delete(PaymentMethod $uuid): void
    {
        $this->getEntityManager()->remove($uuid);
        $this->getEntityManager()->flush();
    }
}
