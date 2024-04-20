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
use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;

class CreditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credit::class);
    }

    /**
     * @throws MathException
     */
    public function addCredit(Client $client, BigInteger|float|int|string $amount): Credit
    {
        $credit = $client->getCredit();

        $credit->setValue($credit->getValue()->plus($amount));

        return $this->save($credit);
    }

    /**
     * @throws MathException
     */
    public function deductCredit(Client $client, BigInteger|float|int|string $amount): Credit
    {
        $credit = $client->getCredit();

        $credit->setValue($credit->getValue()->minus($amount));

        return $this->save($credit);
    }

    private function save(Credit $credit): Credit
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($credit);
        $entityManager->flush();

        return $credit;
    }
}
