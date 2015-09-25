<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Model\Graph;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Money\Money;

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
    public function getTotalIncome(Client $client = null)
    {
        @trigger_error(
            'This function is deprecated, and the one in PaymentRepository should be used instead',
            E_USER_DEPRECATED
        );

        return $this->getTotalByStatus(Graph::STATUS_PAID, $client, 'money');
    }

    /**
     * Get the total amount for outstanding invoices.
     *
     * @param Client $client set this parameter to filter per client
     *
     * @return int
     */
    public function getTotalOutstanding(Client $client = null)
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance)')
            ->where('i.status = :status')
            ->setParameter('status', Graph::STATUS_PENDING);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return $query->getSingleResult('money');
    }

    /**
     * Get the total number of invoices for a specific status.
     *
     * @param string|array $status
     * @param Client       $client set this parameter to filter per client
     *
     * @return int
     */
    public function getCountByStatus($status, Client $client = null)
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
    public function getTotalByStatus($status, Client $client = null, $hydrate = Query::HYDRATE_SINGLE_SCALAR)
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
     * Gets the most recent created invoices.
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentInvoices($limit = 5)
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->orderBy('i.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
