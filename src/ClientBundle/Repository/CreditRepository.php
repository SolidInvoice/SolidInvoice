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
    public function addCredit(Client $client, BigNumber | float | int | string $amount): void
    {
        $amountInt = BigNumber::of(is_float($amount) ? (string) $amount : $amount)
            ->toScale(0, RoundingMode::HalfEven)
            ->toInt();

        $this->createQueryBuilder('c')
            ->update()
            ->set('c.value', 'c.value + :amount')
            ->where('c.client = :client')
            ->setParameter('amount', $amountInt)
            ->setParameter('client', $client)
            ->getQuery()
            ->execute();

        $credit = $client->getCredit();

        if ($this->getEntityManager()->contains($credit)) {
            $this->getEntityManager()->refresh($credit);
        }
    }

    /**
     * @throws MathException
     * @throws InsufficientCreditException
     */
    public function deductCredit(Client $client, BigNumber | float | int | string $amount): void
    {
        $amountInt = BigNumber::of(is_float($amount) ? (string) $amount : $amount)
            ->toScale(0, RoundingMode::HalfEven)
            ->toInt();

        $updated = $this->createQueryBuilder('c')
            ->update()
            ->set('c.value', 'c.value - :amount')
            ->where('c.client = :client')
            ->andWhere('c.value >= :amount')
            ->setParameter('amount', $amountInt)
            ->setParameter('client', $client)
            ->getQuery()
            ->execute();

        if ($updated === 0) {
            throw new InsufficientCreditException();
        }

        $credit = $client->getCredit();

        if ($this->getEntityManager()->contains($credit)) {
            $this->getEntityManager()->refresh($credit);
        }
    }
}
