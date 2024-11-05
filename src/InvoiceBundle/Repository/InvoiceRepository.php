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

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Get the total amount for paid invoices.
     *
     * @throws MathException
     * @deprecated This function is deprecated, and the one in PaymentRepository should be used instead
     */
    public function getTotalIncome(Client $client = null): BigNumber
    {
        @trigger_error(
            'This function is deprecated, and the one in PaymentRepository should be used instead',
            E_USER_DEPRECATED
        );

        return $this->getTotalByStatus(Graph::STATUS_PAID, $client);
    }

    /**
     * Get the total amount for a specific invoice status.
     *
     * @throws MathException
     */
    public function getTotalByStatus(string $status, Client $client = null): BigNumber
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        try {
            return BigNumber::of($qb->getQuery()->getSingleResult());
        } catch (NoResultException | NonUniqueResultException) {
            return BigInteger::zero();
        }
    }

    /**
     * Get the total amount for outstanding invoices.
     */
    public function getTotalOutstanding(Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance)')
            ->where('i.status = :status')
            ->setParameter('status', Graph::STATUS_PENDING);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Get the total number of invoices for a specific status.
     *
     * @param string|string[] $status
     */
    public function getCountByStatus(string | array $status, Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('COUNT(i)');

        if (is_array($status)) {
            $qb->add('where', $qb->expr()->in('i.status', ':status'));
        } else {
            $qb->where('i.status = :status');
        }

        $qb->setParameter('status', $status);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Gets the most recent created invoices.
     *
     * @return Invoice[]
     */
    public function getRecentInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->orderBy('i.created', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{client?: Client} $parameters
     */
    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c');

        if (! empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client'], UlidType::NAME);
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
     * @param list<string> $ids
     */
    public function deleteInvoices(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        array_walk($ids, function (string $id) use ($em): void {
            $entity = $this->find($id);
            $em->remove($entity);
        });

        $em->flush();

        $filters->enable('archivable');
    }

    /**
     * Checks if an invoice is paid in full.
     */
    public function isFullyPaid(Invoice $invoice): bool
    {
        $invoiceTotal = $invoice->getTotal();

        $totalPaid = $this->getEntityManager()
            ->getRepository(Payment::class)
            ->getTotalPaidForInvoice($invoice);

        return $totalPaid->isEqualTo($invoiceTotal) || $totalPaid->isGreaterThan($invoiceTotal);
    }

    public function getTotalOutstandingForClient(Client $client): BigInteger
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance) as total')
            ->where('i.status = :status')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('status', Graph::STATUS_PENDING);

        $query = $qb->getQuery();

        try {
            return BigInteger::of((string) $query->getSingleScalarResult());
        } catch (MathException | NoResultException | NonUniqueResultException) {
            return BigInteger::zero();
        }
    }
}
