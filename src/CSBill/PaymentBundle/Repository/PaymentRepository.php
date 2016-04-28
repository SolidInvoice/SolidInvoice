<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\PaymentBundle\Model\Status;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Money\Money;

class PaymentRepository extends EntityRepository
{
    /**
     * Gets the total income that was received.
     *
     * @param \CSBill\ClientBundle\Entity\Client $client
     *
     * @return \Money\Money
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTotalIncome(Client $client = null)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('SUM(p.totalAmount)')
            ->where('p.status = :status')
            ->setParameter('status', Status::STATUS_CAPTURED);

        if (null !== $client) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        return $query->getSingleResult('money');
    }

    /**
     * Returns an array of all the payments for an invoice.
     *
     * @param Invoice $invoice
     * @param string  $orderField
     * @param string  $sort
     *
     * @return array
     */
    public function getPaymentsForInvoice(Invoice $invoice, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.invoice = :invoice')
            ->setParameter('invoice', $invoice);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param string $orderField
     * @param string $sort
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getPaymentQueryBuilder($orderField = null, $sort = 'DESC')
    {
	if (null === $orderField) {
	    $orderField = 'p.created';
	}

	$queryBuilder = $this->createQueryBuilder('p');

	$queryBuilder->select(
	    'p.id',
	    'p.totalAmount',
	    'p.currencyCode',
	    'p.created',
	    'p.completed',
	    'p.status',
	    'i.id as invoice',
	    'm.name as method',
	    'p.message'
	)
	    ->join('p.method', 'm')
	    ->join('p.invoice', 'i')
	    ->orderBy($orderField, $sort);

	return $queryBuilder;
    }

    /**
     * Returns an array of all the payments for an invoice.
     *
     * @param Invoice $invoice
     *
     * @return Money
     */
    public function getTotalPaidForInvoice(Invoice $invoice)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->select('SUM(p.totalAmount) as total')
            ->where('p.invoice = :invoice')
            ->andWhere('p.status = :status')
            ->setParameter('invoice', $invoice)
            ->setParameter('status', Status::STATUS_CAPTURED);

        $query = $queryBuilder->getQuery();

        return $query->getSingleResult('money');
    }

    /**
     * Returns an array of all the payments for a client.
     *
     * @param Client $client
     * @param string $orderField
     * @param string $sort
     *
     * @return array
     */
    public function getPaymentsForClient(Client $client, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.client = :client')
            ->setParameter('client', $client);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Gets the most recent created payments.
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentPayments($limit = 5)
    {
        $qb = $this->getPaymentQueryBuilder();

        $qb->addSelect(
            'c.name as client',
            'c.id as client_id'
        )
            ->join('p.client', 'c')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param \DateTime $timestamp
     *
     * @return array
     */
    public function getPaymentsList(\DateTime $timestamp = null)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select('p.totalAmount', 'p.created')
            ->join('p.method', 'm')
            ->where('p.created >= :date')
            ->setParameter('date', $timestamp)
            ->groupBy('p.created')
            ->orderBy('p.created', 'ASC');

        $query = $queryBuilder->getQuery();

        $payments = $this->formatDate($query);

        $results = array();

        foreach ($payments as $date => $amount) {
            $results[] = array(strtotime($date) * 1000, $amount);
        }

        return $results;
    }

    /**
     * @param \Doctrine\ORM\Query $query
     * @param string              $dateFormat
     *
     * @return array
     */
    private function formatDate($query, $dateFormat = 'Y-m-d')
    {
	$payments = [];

	foreach ($query->getArrayResult() as $result) {
	    /** @var \DateTime $created */
	    $created = $result['created'];
	    $date = $created->format($dateFormat);
	    if (!isset($payments[$date])) {
		$payments[$date] = 0;
	    }
	    $payments[$date] += $result['totalAmount'];
	}

	return $payments;
    }

    /**
     * @return array
     */
    public function getPaymentsByMonth()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.totalAmount',
            'p.created'
        )
            ->join('p.method', 'm')
            ->where('p.created >= :date')
            ->setParameter('date', new \DateTime('-1 Year'))
            ->groupBy('p.created')
            ->orderBy('p.created', 'ASC');

        $query = $queryBuilder->getQuery();

        $payments = $this->formatDate($query, 'F Y');

        return $payments;
    }

    /**
     * @param Payment[]|Collection $payments
     * @param string               $status
     *
     * @return mixed
     */
    public function updatePaymentStatus($payments, $status)
    {
        if (!is_array($payments) && !$payments instanceof \Traversable) {
            $payments = array($payments);
        }

        if ($payments instanceof Collection) {
            $payments = $payments->toArray();
        }

        $qb = $this->createQueryBuilder('p');

        $qb->update()
            ->set('p.status', ':status')
            ->where('p.id in (:payments)')
            ->setParameter('status', $status)
            ->setParameter('payments', $payments);

        return $qb->getQuery()->execute();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getGridQuery()
    {
	$qb = $this->createQueryBuilder('p');

	$qb->select(['p', 'c', 'i', 'm'])
	    ->join('p.client', 'c')
	    ->join('p.invoice', 'i')
	    ->join('p.method', 'm');

	return $qb;
    }
}
