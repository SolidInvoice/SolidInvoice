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
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\CoreBundle\Util\ArrayUtil;

/**
 * ClientRepository.
 *
 * Custom Repository class for managing clients
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Gets total number of clients.
     *
     * @param string $status
     */
    public function getTotalClients(string $status = null): int
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('COUNT(c.id)');

        if (null !== $status) {
            $qb->where('c.status = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created clients.
     *
     * @param int $limit
     */
    public function getRecentClients($limit = 5): array
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

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

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
     * Archives a list of clients.
     */
    public function archiveClients(array $ids): void
    {
        // @TODO: Validate that we have an array of integers and valid client IDs

        /** @var Client[] $clients */
        $clients = $this->findBy(['id' => $ids]);

        $em = $this->getEntityManager();

        foreach ($clients as $client) {
            $client->setArchived(true)
                ->setStatus(Status::STATUS_ARCHIVED);

            $em->persist($client);
        }

        $em->flush();
    }

    public function restoreClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        /** @var Client[] $clients */
        $clients = $this->findBy(['id' => $ids]);

        foreach ($clients as $client) {
            $client->setArchived(null)
                ->setStatus(Status::STATUS_ACTIVE);

            $em->persist($client);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * @throws ORMException|OptimisticLockException|InvalidArgumentException
     */
    public function deleteClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        /** @var Client[] $clients */
        $clients = $this->findBy(['id' => $ids]);

        array_walk($clients, function (object $entity) use ($em): void {
            $em->remove($entity);
        });

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    public function delete(Client $client)
    {
        $this->deleteClients([$client->getId()]);
    }
}
