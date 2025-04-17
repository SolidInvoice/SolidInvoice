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

namespace SolidInvoice\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<ApiTokenHistory>
 */
class ApiTokenHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiTokenHistory::class);
    }

    public function addHistory(ApiTokenHistory $history, string $token): void
    {
        $entityManager = $this->getEntityManager();

        /** @var ApiToken $apiToken */
        $apiToken = $entityManager
            ->getRepository(ApiToken::class)
            ->findOneBy(['token' => $token]);

        $apiToken->addHistory($history);

        $entityManager->persist($history);
        $entityManager->flush();

        // delete the history for all but the last 100 records for each api token
        // This is to ensure the database doesn't grow to an unmanageable size
        // @TODO: This needs to be done in a safer manner
        // If multiple api requests happen at the same time, this can cause some inconsistencies with the data
        $queryBuilder = $this->createQueryBuilder('a');

        $ids = $queryBuilder
            ->select('a.id')
            ->where('a.token = :token')
            ->orderBy('a.created', Order::Descending->value)
            ->setMaxResults(100)
            ->getQuery()
            ->getDQL();

        $qb = $this->createQueryBuilder('h');
        $qb->delete()
            ->where($qb->expr()->in('h.id', $ids))
            ->setParameter('token', $apiToken->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();
    }
}
