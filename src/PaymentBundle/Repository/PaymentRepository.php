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

use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use function array_map;

/**
 * @extends ServiceEntityRepository<Payment>
 * @see \SolidInvoice\PaymentBundle\Tests\Repository\PaymentRepositoryTest
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Gets the total income that was received.
     *
     * @return Money[]
     */
    public function getTotalIncome(): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('SUM(p.totalAmount) as total', 'p.currencyCode')
            ->where('p.status = :status')
            ->groupBy('p.currencyCode')
            ->setParameter('status', Status::STATUS_CAPTURED);

        $query = $qb->getQuery();

        $results = [];

        foreach ($query->getArrayResult() as $result) {
            $results[] = new Money($result['total'], new Currency($result['currencyCode']));
        }

        return $results;
    }

    /**
     * Returns an array of all the payments for an invoice.
     *
     * @return array<string, string|int|DateTimeInterface>
     */
    public function getPaymentsForInvoice(Invoice $invoice, string $orderField = null, string $sort = 'DESC'): array
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.invoice = :invoice')
            ->setParameter('invoice', $invoice->getId(), UuidBinaryOrderedTimeType::NAME);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    protected function getPaymentQueryBuilder(string $orderField = null, string $sort = 'DESC'): QueryBuilder
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
        if (! $invoice->getId() instanceof UuidInterface) {
            return 0;
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->select('SUM(p.totalAmount) as total')
            ->where('p.invoice = :invoice')
            ->andWhere('p.status = :status')
            ->setParameter('invoice', $invoice->getId(), UuidBinaryOrderedTimeType::NAME)
            ->setParameter('status', Status::STATUS_CAPTURED);

        $query = $queryBuilder->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Returns an array of all the payments for a client.
     *
     * @return array<string, string|int|DateTimeInterface>
     */
    public function getPaymentsForClient(Client $client, string $orderField = null, string $sort = 'DESC'): array
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.client = :client')
            ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Gets the most recent created payments.
     *
     * @return array<string, array<string|int|DateTimeInterface|Money>>
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

        return array_map(static function (array $payment): array {
            $payment['amount'] = new Money($payment['totalAmount'], new Currency($payment['currencyCode']));

            return $payment;
        }, $qb->getQuery()->getArrayResult());
    }

    /**
     * @deprecated Use getPaymentsByMonth instead
     * @return array<array<int>>
     */
    public function getPaymentsList(DateTime $timestamp = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select('p.totalAmount', 'p.created');

        if ($timestamp instanceof DateTime) {
            $queryBuilder->andWhere('p.created >= :date')
                ->setParameter('date', $timestamp);
        }

        $queryBuilder
            ->groupBy('p.created, p.totalAmount')
            ->orderBy('p.created', Criteria::ASC);

        $query = $queryBuilder->getQuery();

        $payments = $this->formatDate($query);

        $results = [];

        foreach ($payments as $date => $amount) {
            $results[] = [strtotime($date) * 1000, $amount];
        }

        return $results;
    }

    /**
     * @return array<string, int>
     */
    private function formatDate(Query $query, string $dateFormat = 'Y-m-d'): array
    {
        $payments = [];

        foreach ($query->getArrayResult() as $result) {
            /** @var DateTime $created */
            $created = $result['created'];

            $date = $created->format($dateFormat);
            if (! isset($payments[$date])) {
                $payments[$date] = 0;
            }

            $payments[$date] += $result['totalAmount'];
        }

        return $payments;
    }

    /**
     * @return array<string, int>
     */
    public function getPaymentsByMonth(): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            [
                'p.totalAmount',
                'p.created',
            ]
        )
            ->where('p.created >= :date')
            ->setParameter('date', new DateTime('-1 Year'))
            ->groupBy('p.created, p.totalAmount')
            ->orderBy('p.created', Criteria::ASC);

        $query = $queryBuilder->getQuery();

        return $this->formatDate($query, 'F Y');
    }

    /**
     * @param Payment[]|Collection<int, Payment> $payments
     */
    public function updatePaymentStatus(iterable $payments, string $status): int
    {
        foreach ($payments as $payment) {
            $payment->setStatus($status);
        }

        $this->getEntityManager()->flush();

        return count($payments);
    }

    /**
     * @param array{client?: Client, invoice?: Invoice} $parameters
     */
    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select(['p', 'c', 'i', 'm'])
            ->join('p.client', 'c')
            ->join('p.invoice', 'i')
            ->join('p.method', 'm');

        if (isset($parameters['invoice'])) {
            $qb->andWhere('p.invoice = :invoice');
            $qb->setParameter('invoice', $parameters['invoice'], UuidBinaryOrderedTimeType::NAME);
        }

        if (isset($parameters['client'])) {
            $qb->andWhere('p.client = :client');
            $qb->setParameter('client', $parameters['client'], UuidBinaryOrderedTimeType::NAME);
        }

        return $qb;
    }

    public function updateCurrency(Client $client): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $currency = $client->getCurrency();

        if (! $currency instanceof Currency) {
            return;
        }

        $qb = $this->createQueryBuilder('p');

        $qb->update()
            ->set('p.currencyCode', ':currency')
            ->where('p.client = :client')
            ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME)
            ->setParameter('currency', $currency->getCode());

        $qb->getQuery()->execute();

        $filters->enable('archivable');
    }

    public function getTotalIncomeForClient(Client $client): ?Money
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('SUM(p.totalAmount) as total', 'p.currencyCode')
            ->where('p.status = :status')
            ->andWhere('p.client = :client')
            ->groupBy('p.currencyCode')
            ->setParameter('client', $client->getId(), UuidBinaryOrderedTimeType::NAME)
            ->setParameter('status', Status::STATUS_CAPTURED);

        $query = $qb->getQuery();

        $result = $query->getResult();

        if ([] === $result) {
            return null;
        }

        return new Money($result[0]['total'], new Currency($result[0]['currencyCode']));
    }
}
