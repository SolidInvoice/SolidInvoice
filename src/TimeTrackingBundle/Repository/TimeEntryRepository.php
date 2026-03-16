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

namespace SolidInvoice\TimeTrackingBundle\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function array_map;

/**
 * @extends EntityRepository<TimeEntry>
 */
class TimeEntryRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeEntry::class);
    }

    /**
     * Get total pending duration in seconds for a client.
     */
    public function getTotalPendingDuration(Client $client): int
    {
        $result = $this->createQueryBuilder('te')
            ->select('SUM(te.duration)')
            ->where('te.client = :client')
            ->andWhere('te.status = :status')
            ->setParameter('client', $client)
            ->setParameter('status', TimeEntryStatus::Pending->value)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Get the count of pending entries for a client.
     */
    public function getPendingCountForClient(Client $client): int
    {
        return (int) $this->createQueryBuilder('te')
            ->select('COUNT(te.id)')
            ->where('te.client = :client')
            ->andWhere('te.status = :status')
            ->setParameter('client', $client)
            ->setParameter('status', TimeEntryStatus::Pending->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Build a query for time entries with optional filters.
     * Used by DataGrid with context parameters.
     *
     * @param array{client_id?: string, status?: string} $context
     */
    public function buildFilteredQuery(QueryBuilder $qb, array $context = []): QueryBuilder
    {
        if (isset($context['client_id'])) {
            $qb->andWhere('te.client = :client_id')
                ->setParameter('client_id', $context['client_id']);
        }

        return $qb;
    }

    /**
     * Find time entries by their IDs.
     *
     * @param string[] $ids
     * @return TimeEntry[]
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $em = $this->getEntityManager();
        $platform = $em->getConnection()->getDatabasePlatform();
        $ulidType = Type::getType(UlidType::NAME);

        $dbIds = array_map(
            static fn (string $id): string => $ulidType->convertToDatabaseValue(Ulid::fromString($id), $platform),
            $ids,
        );

        return $this->createQueryBuilder('te')
            ->where('te.id IN (:ids)')
            ->setParameter('ids', $dbIds, ArrayParameterType::STRING)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete time entries by their IDs.
     *
     * @param string[] $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $em = $this->getEntityManager();

        foreach ($this->findByIds($ids) as $entry) {
            $em->remove($entry);
        }

        $em->flush();
    }
}
