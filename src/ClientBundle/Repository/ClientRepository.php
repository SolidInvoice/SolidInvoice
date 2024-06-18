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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\CoreBundle\Util\ArrayUtil;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function getTotalClients(string $status = null): int
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('COUNT(c.id)');

        if (null !== $status) {
            $qb->where('c.status = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * @return Client[]
     */
    public function getRecentClients(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select(
            [
                'c.id',
                'c.name',
                'c.created',
                'c.status',
            ]
        )
            ->orderBy('c.created', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getStatusList(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('DISTINCT c.status');

        return ArrayUtil::column($qb->getQuery()->getResult(), 'status');
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c');

        return $qb;
    }

    public function getArchivedGridQuery(): QueryBuilder
    {
        $this->getEntityManager()->getFilters()->disable('archivable');

        $qb = $this->createQueryBuilder('c');

        $qb->select('c')
            ->where('c.archived is not null');

        return $qb;
    }

    /**
     * @param list<int> $ids
     */
    public function archiveClients(array $ids): void
    {
        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $client = $this->find($id);

            if (! $client instanceof Client) {
                continue;
            }

            $client
                ->setArchived(true)
                ->setStatus(Status::STATUS_ARCHIVED);

            $em->persist($client);
        }

        $em->flush();
    }

    /**
     * @param list<int> $ids
     */
    public function restoreClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $client = $this->find($id);

            if (! $client instanceof Client) {
                continue;
            }

            $client
                ->setArchived(null)
                ->setStatus(Status::STATUS_ACTIVE);

            $em->persist($client);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * @param list<string> $ids
     */
    public function deleteClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $entity = $this->find($id);

            if (! $entity instanceof Client) {
                continue;
            }

            $em->remove($entity);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    public function delete(Client $client): void
    {
        $this->getEntityManager()->remove($client);
    }
}
