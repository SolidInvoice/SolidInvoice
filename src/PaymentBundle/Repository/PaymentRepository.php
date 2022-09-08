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

namespace SolidInvoice\PaymentBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use Traversable;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Gets the total income that was received.
     *
     * @param Client $client
     * @param bool   $groupByCurrency
     *
     * @return array|int
     *
     * @throws NonUniqueResultException
     */
    public function getTotalIncome(Client $client = null, $groupByCurrency = false)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('SUM(p.totalAmount)')
            ->where('p.status = :status')
            ->setParameter('status', Status::STATUS_CAPTURED);

        if ($groupByCurrency) {
            $qb->select('SUM(p.totalAmount) as totalAmount', 'p.currencyCode')
                ->groupBy('p.currencyCode');
        }

        if (null !== $client) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $client);
        }

        $query = $qb->getQuery();

        if ($groupByCurrency) {
            return $query->getArrayResult();
        }

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Returns an array of all the payments for an invoice.
     *
     * @param string $orderField
     * @param string $sort
     */
    public function getPaymentsForInvoice(Invoice $invoice, string $orderField = null, $sort = 'DESC'): array
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
     */
    protected function getPaymentQueryBuilder(string $orderField = null, $sort = 'DESC'): QueryBuilder
    {
        if (null === $orderField) {
            $orderField = 'p.created';
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            [
                'p.id',
                'p.totalAmount',
                'p.currencyCode',
                'p.created',
                'p.completed',
                'p.status',
                'i.id as invoice',
                'm.name as method',
                'p.message',
            ]
        )
            ->join('p.method', 'm')
            ->join('p.invoice', 'i')
            ->orderBy($orderField, $sort);

        return $queryBuilder;
    }

    /**
     * Returns an array of all the payments for an invoice.
     */
    public function getTotalPaidForInvoice(Invoice $invoice): int
    {
        if (!$invoice->getId()) {
            return 0;
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->select('SUM(p.totalAmount) as total')
            ->where('p.invoice = :invoice')
            ->andWhere('p.status = :status')
            ->setParameter('invoice', $invoice)
            ->setParameter('status', Status::STATUS_CAPTURED);

        $query = $queryBuilder->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Returns an array of all the payments for a client.
     *
     * @param string $orderField
     * @param string $sort
     */
    public function getPaymentsForClient(Client $client, string $orderField = null, $sort = 'DESC'): array
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
     */
    public function getRecentPayments(int $limit = 5): array
    {
        $qb = $this->getPaymentQueryBuilder();

        $qb->addSelect(
            [
                'c.name as client',
                'c.id as client_id',
            ]
        )
            ->join('p.client', 'c')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param DateTime $timestamp
     */
    public function getPaymentsList(DateTime $timestamp = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select('p.totalAmount', 'p.created')
            ->join('p.method', 'm')
            ->where('p.created >= :date')
            ->setParameter('date', $timestamp)
            ->groupBy('p.created')
            ->orderBy('p.created', Criteria::ASC);

        $query = $queryBuilder->getQuery();

        $payments = $this->formatDate($query);

        $results = [];

        foreach ($payments as $date => $amount) {
            $results[] = [strtotime($date) * 1000, $amount];
        }

        return $results;
    }

    private function formatDate(Query $query, string $dateFormat = 'Y-m-d'): array
    {
        $payments = [];

        foreach ($query->getArrayResult() as $result) {
            /** @var DateTime $created */
            $created = $result['created'];

            $date = $created->format($dateFormat);
            if (!isset($payments[$date])) {
                $payments[$date] = 0;
            }

            $payments[$date] += $result['totalAmount'];
        }

        return $payments;
    }

    public function getPaymentsByMonth(): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            [
                'p.totalAmount',
                'p.created',
            ]
        )
            ->join('p.method', 'm')
            ->where('p.created >= :date')
            ->setParameter('date', new DateTime('-1 Year'))
            ->groupBy('p.created')
            ->orderBy('p.created', Criteria::ASC);

        $query = $queryBuilder->getQuery();

        return $this->formatDate($query, 'F Y');
    }

    /**
     * @param Payment[]|Collection $payments
     *
     * @return mixed
     */
    public function updatePaymentStatus($payments, string $status)
    {
        if (!is_array($payments) && !$payments instanceof Traversable) {
            $payments = [$payments];
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

    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select(['p', 'c', 'i', 'm'])
            ->join('p.client', 'c')
            ->join('p.invoice', 'i')
            ->join('p.method', 'm');

        if (isset($parameters['invoice'])) {
            $qb->where('p.invoice = :invoice');
            $qb->setParameter('invoice', $parameters['invoice']);
        }

        if (isset($parameters['client'])) {
            $qb->where('p.client = :client');
            $qb->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    public function updateCurrency(Client $client)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $currency = $client->getCurrency();

        $qb = $this->createQueryBuilder('p');

        $qb->update()
            ->set('p.currencyCode', ':currency')
            ->where('p.client = :client')
            ->setParameter('client', $client)
            ->setParameter('currency', $currency);

        $qb->getQuery()->execute();

        $filters->enable('archivable');
    }
}
