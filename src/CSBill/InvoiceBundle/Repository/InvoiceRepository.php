<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Status;
use Doctrine\ORM\EntityRepository;

class InvoiceRepository extends EntityRepository
{
    /**
     * Get the total amount for paid invoices
     *
     * @param  Client $client set this parameter to filter per client
     * @return int
     */
    public function getTotalIncome(Client $client = null)
    {
        return $this->getTotalByStatus('paid', $client);
    }

    /**
     * Get the total amount for outstanding invoices
     *
     * @param  Client $client set this parameter to filter per client
     * @return int
     */
    public function getTotalOutstanding(Client $client = null)
    {
        return $this->getTotalByStatus('pending', $client);
    }

    /**
     * Get the total number of invoices for a specific status
     *
     * @param $status
     * @param  Client $client set this paramater to filter per client
     * @return int
     */
    public function getCountByStatus($status, Client $client = null)
    {
        if (!$status instanceof Status) {
            $status = $this->getEntityManager()->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);
        }

        $qb = $this->createQueryBuilder('i');

        $qb->select('COUNT(i)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
               ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * Get the total amount for a specific invoice status
     *
     * @param  string $status
     * @param  Client $client set this paramater to filter per client
     * @return int
     */
    public function getTotalByStatus($status, Client $client = null)
    {
        if (!$status instanceof Status) {
            $status = $this->getEntityManager()->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);
        }

        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if (null !== $client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created invoices
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentInvoices($limit = 5)
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(
            'i.id',
            'c.name as client',
            'c.id as client_id',
            'i.discount',
            'i.total',
            'i.created',
            's.name as status',
            's.label as status_label'
        )
            ->join('i.client', 'c')
            ->join('i.status', 's')
            ->orderBy('i.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }
}
