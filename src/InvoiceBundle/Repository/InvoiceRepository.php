<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Repository;

use Carbon\Carbon;
use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class InvoiceRepository extends EntityRepository
{
    /**
     * Get the total amount for paid invoices.
     *
     * @param Client $client set this parameter to filter per client
     *
     * @deprecated This function is deprecated, and the one in PaymentRepository should be used instead
     *
     * @return int
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
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     *
     * @return int
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
     *
     * @return int
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
     *
     * @return array
     */
    public function getRecentInvoices($limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->orderBy('i.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function getRecurringInvoices(): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->select('i', 'r')
            ->join('i.recurringInfo', 'r')
            ->where('i.recurring = 1')
            ->andWhere('r.dateStart <= :now')
            ->setParameter('now', Carbon::now());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $parameters
     *
     * @return QueryBuilder
     */
    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c')
            ->where('i.recurring = 0');

        if (!empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    /**
     * @param array $parameters
     *
     * @return QueryBuilder
     */
    public function getRecurringGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c', 'r'])
            ->join('i.client', 'c')
            ->join('i.recurringInfo', 'r', Join::WITH, 'i.recurring = 1');

        if (!empty($parameters['client'])) {
            $qb->where('i.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
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
     * @param Client $client
     */
    public function updateCurrency(Client $client)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');
        $filters->disable('softdeleteable');

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
                ->from('CSBillInvoiceBundle:Item', 'it')
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
        $filters->enable('softdeleteable');
    }
}
