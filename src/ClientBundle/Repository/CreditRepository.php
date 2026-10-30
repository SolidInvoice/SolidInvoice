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

namespace SolidInvoice\ClientBundle\Repository;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Credit>
 */
class CreditRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credit::class);
    }

    /**
     * @throws MathException
     */
    public function addCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $credit = $client->getCredit();
        $amountInt = BigDecimal::of(is_float($amount) ? (string) $amount : $amount)
            ->toScale(0, RoundingMode::HalfEven)
            ->toInt();

        $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Credit::class, 'c')
            ->set('c.value', 'c.value + :amount')
            ->where('c.id = :id')
            ->setParameter('amount', $amountInt)
            ->setParameter('id', $credit->getId())
            ->getQuery()
            ->execute();

        $this->getEntityManager()->refresh($credit);

        return $credit;
    }

    /**
     * @throws MathException
     * @throws InsufficientCreditException
     */
    public function deductCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $credit = $client->getCredit();
        $amountInt = BigDecimal::of(is_float($amount) ? (string) $amount : $amount)
            ->toScale(0, RoundingMode::HalfEven)
            ->toInt();

        // Atomic read-check-write: the WHERE clause guards against overdraft.
        // Zero rows updated means another request already consumed the balance.
        $updated = $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Credit::class, 'c')
            ->set('c.value', 'c.value - :amount')
            ->where('c.id = :id')
            ->andWhere('c.value >= :amount')
            ->setParameter('amount', $amountInt)
            ->setParameter('id', $credit->getId())
            ->getQuery()
            ->execute();

        if ($updated === 0) {
            throw new InsufficientCreditException();
        }

        $this->getEntityManager()->refresh($credit);

        return $credit;
    }
}
