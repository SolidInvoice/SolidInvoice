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

namespace SolidInvoice\InvoiceBundle\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;

/**
 * @extends ServiceEntityRepository<RecurringInvoice>
 */
class RecurringInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringInvoice::class);
    }

    /**
     * @param array{client?: Client} $parameters
     */
    public function getRecurringGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c');

        if (! empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    public function getArchivedGridQuery(): QueryBuilder
    {
        $this->getEntityManager()->getFilters()->disable('archivable');

        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c')
            ->where('i.archived is not null');

        return $qb;
    }

    /**
     * @param list<int> $ids
     */
    public function deleteInvoices(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        /** @var RecurringInvoice[] $invoices */
        $invoices = $this->findBy(['id' => $ids]);

        foreach ($invoices as $invoice) {
            $em->remove($invoice);
        }

        $em->flush();

        $filters->enable('archivable');
    }

    /**
     * @return iterable<RecurringInvoice>
     */
    public function getActiveRecurringInvoices(): iterable
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.status = :status')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->setParameter('status', 'active')
            ->getQuery()
            ->toIterable();
    }

    /**
     * Get upcoming recurring invoices that will be generated within the next N days.
     *
     * @return RecurringInvoice[]
     */
    public function getUpcomingRecurringInvoices(int $days = 7, int $limit = 3): array
    {
        $now = new DateTime();
        $futureDate = new DateTime(sprintf('+%d days', $days));

        $qb = $this->createQueryBuilder('ri');

        $qb
            ->innerJoin('ri.client', 'c')
            ->addSelect('c')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->addSelect('ro')
            ->where('ri.status = :status')
            ->andWhere('ri.dateStart <= :futureDate')
            ->andWhere('(ri.dateEnd IS NULL OR ri.dateEnd >= :now)')
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('ri.dateStart', Criteria::ASC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
