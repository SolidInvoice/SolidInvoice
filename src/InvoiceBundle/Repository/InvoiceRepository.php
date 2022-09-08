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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Get the total amount for paid invoices.
     *
     * @param Client $client set this parameter to filter per client
     *
     * @deprecated This function is deprecated, and the one in PaymentRepository should be used instead
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
     * @param string $status
     * @param Client $client  filter per client
     * @param int    $hydrate
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalByStatus($status, Client $client = null, $hydrate = Query::HYDRATE_SINGLE_SCALAR): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return $query->getSingleResult($hydrate);
    }

    /**
     * Get the total amount for outstanding invoices.
     *
     * @param Client $client set this parameter to filter per client
     */
    public function getTotalOutstanding(Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance.value)')
            ->where('i.status = :status')
            ->setParameter('status', Graph::STATUS_PENDING);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get the total number of invoices for a specific status.
     *
     * @param string|array $status
     * @param Client       $client set this parameter to filter per client
     */
    public function getCountByStatus($status, Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('COUNT(i)');

        if (is_array($status)) {
            /* @noinspection PhpParamsInspection */
            $qb->add('where', $qb->expr()->in('i.status', ':status'));
        } else {
            $qb->where('i.status = :status');
        }

        $qb->setParameter('status', $status);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created invoices.
     *
     * @param int $limit
     */
    public function getRecentInvoices($limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->orderBy('i.created', Criteria::DESC)
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function getGridQuery(array $parameters = []): QueryBuilder
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
            ->setParameter('client', $client)
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
                ->setParameter('client', $client)
                ->setParameter('currency', $currency);

            $qbi->getQuery()->execute();
        }

        $filters->enable('archivable');
    }

    public function deleteInvoices(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        /** @var Invoice[] $invoices */
        $invoices = $this->findBy(['id' => $ids]);

        array_walk($invoices, function (object $entity) use ($em): void {
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
}
