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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;

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
     * @deprecated This function is deprecated, and the one in PaymentRepository should be used instead
     *
     * @throws NoResultException|NonUniqueResultException
     */
    public function getTotalIncome(Client $client = null): int
    {
        @trigger_error(
            'This function is deprecated, and the one in PaymentRepository should be used instead',
            E_USER_DEPRECATED
        );

        return $this->getTotalByStatus(Graph::STATUS_PAID, $client, 'money');
    }

    /**
     * Get the total amount for a specific invoice status.
     *
     * @param int|string $hydrate
     *
     * @throws NoResultException|NonUniqueResultException
     */
    public function getTotalByStatus(string $status, Client $client = null, $hydrate = AbstractQuery::HYDRATE_SINGLE_SCALAR): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME);
        }

        return $qb->getQuery()->getSingleResult($hydrate);
    }

    /**
     * Get the total amount for outstanding invoices.
     */
    public function getTotalOutstanding(Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance.value)')
            ->where('i.status = :status')
            ->setParameter('status', Graph::STATUS_PENDING);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Get the total number of invoices for a specific status.
     *
     * @param string|string[] $status
     */
    public function getCountByStatus($status, Client $client = null): int
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
                ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
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
                ->setParameter('client', $parameters['client'], UuidBinaryOrderedTimeType::NAME);
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

    public function updateCurrency(Client $client): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $currency = $client->getCurrency();

        $qb = $this->createQueryBuilder('i');

        $qb->update()
            ->set('i.total.currency', ':currency')
            ->set('i.baseTotal.currency', ':currency')
            ->set('i.balance.currency', ':currency')
            ->set('i.tax.currency', ':currency')
            ->where('i.client = :client')
            ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME)
            ->setParameter('currency', $currency);

        if ($qb->getQuery()->execute()) {
            $qbi = $this->getEntityManager()->createQueryBuilder();

            $qbi->update()
                ->from(Item::class, 'it')
                ->set('it.price.currency', ':currency')
                ->set('it.total.currency', ':currency')
                ->where(
                    $qbi->expr()->in(
                        'it.invoice',
                        $this->createQueryBuilder('i')
                            ->select('i.id')
                            ->where('i.client = :client')
                            ->getDQL()
                    )
                )
                ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME)
                ->setParameter('currency', $currency);

            $qbi->getQuery()->execute();
        }

        $filters->enable('archivable');
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

        $totalPaid = new Money(
            $this->getEntityManager()
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice),
            $invoiceTotal->getCurrency()
        );

        return $totalPaid->equals($invoiceTotal) || $totalPaid->greaterThan($invoiceTotal);
    }

    public function getTotalOutstandingForClient(Client $client): ?Money
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance.value) as total, i.balance.currency as currency')
            ->where('i.status = :status')
            ->andWhere('i.client = :client')
            ->groupBy('i.balance.currency')
            ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME)
            ->setParameter('status', Graph::STATUS_PENDING);

        $query = $qb->getQuery();

        $result = $query->getArrayResult();

        if ([] === $result) {
            return null;
        }

        return new Money($result[0]['total'], new Currency($result[0]['currency']));
    }
}
