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

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
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
     * Atomically add credit for a client.
     *
     * Uses a single UPDATE statement so concurrent calls cannot interleave.
     *
     * @throws MathException
     */
    public function addCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $amount = BigInteger::of(BigNumber::of($amount));

        $this->createQueryBuilder('c')
            ->update()
            ->set('c.value', 'c.value + :amount')
            ->where('c.client = :client')
            ->setParameter('amount', $amount, 'BigInteger')
            ->setParameter('client', $client)
            ->getQuery()
            ->execute();

        $credit = $client->getCredit();
        $this->getEntityManager()->refresh($credit);

        return $credit;
    }

    /**
     * Atomically deduct credit for a client, guarding against over-deduction.
     *
     * The WHERE condition `value_amount >= :amount` folds the sufficiency check
     * into the same write, making concurrent deductions safe: the second deduction
     * that would push the balance negative simply matches zero rows and throws
     * InsufficientCreditException rather than silently corrupting the balance.
     *
     * @throws MathException
     * @throws InsufficientCreditException when the client does not have enough credit
     */
    public function deductCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $amount = BigInteger::of(BigNumber::of($amount));

        $rows = $this->createQueryBuilder('c')
            ->update()
            ->set('c.value', 'c.value - :amount')
            ->where('c.client = :client')
            ->andWhere('c.value >= :amount')
            ->setParameter('amount', $amount, 'BigInteger')
            ->setParameter('client', $client)
            ->getQuery()
            ->execute();

        if (0 === $rows) {
            throw new InsufficientCreditException($client);
        }

        $credit = $client->getCredit();
        $this->getEntityManager()->refresh($credit);

        return $credit;
    }
}
