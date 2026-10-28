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

namespace SolidInvoice\DashboardBundle\Activity;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use function array_slice;
use function in_array;
use function usort;

/**
 * What happened recently, newest first, for the dashboard widget and the activity API.
 *
 * Nothing records when a document changed status, so each entry is derived from the record as it
 * is now: an invoice still pending was sent when it was last changed, a quote that is accepted
 * was accepted when it was last changed, and so on. An entry stops showing once its record moves
 * on (a sent invoice that is then paid shows as the payment).
 *
 * Entries are arrays: `type`, `date`, `id`, `client`, `clientId`, plus `invoiceId`/`invoiceUlid`/
 * `quoteId`, `amount` (minor units), `currency`, `status` and `method` where they apply.
 */
final readonly class ActivityFeed
{
    public const string PAYMENT = 'payment';

    public const string INVOICE_SENT = 'invoice_sent';

    public const string INVOICE_OVERDUE = 'invoice_overdue';

    public const string QUOTE_SENT = 'quote_sent';

    public const string QUOTE_ACCEPTED = 'quote_accepted';

    public const string QUOTE_DECLINED = 'quote_declined';

    public const string CLIENT_CREATED = 'client_created';

    public const string RECURRING_GENERATED = 'recurring_generated';

    public const array TYPES = [
        self::PAYMENT,
        self::INVOICE_SENT,
        self::INVOICE_OVERDUE,
        self::QUOTE_SENT,
        self::QUOTE_ACCEPTED,
        self::QUOTE_DECLINED,
        self::CLIENT_CREATED,
        self::RECURRING_GENERATED,
    ];

    public function __construct(
        private PaymentRepository $paymentRepository,
        private InvoiceRepository $invoiceRepository,
        private QuoteRepository $quoteRepository,
        private ClientRepository $clientRepository,
    ) {
    }

    /**
     * Each source is read up to $offset + $limit entries and the merge is cut down, so a page costs
     * one bounded query per source.
     *
     * @param list<string> $types
     *
     * @return list<array<string, mixed>>
     *
     * @throws MathException
     */
    public function entries(int $limit, int $offset = 0, ?Client $client = null, array $types = self::TYPES): array
    {
        $take = $offset + $limit;
        $entries = [];

        if (in_array(self::PAYMENT, $types, true)) {
            $entries = [...$entries, ...$this->payments($take, $client)];
        }

        if (in_array(self::INVOICE_SENT, $types, true)) {
            $entries = [...$entries, ...$this->invoices(self::INVOICE_SENT, $this->invoiceQuery($take, $client)->andWhere('i.status = :status')->setParameter('status', InvoiceStatus::Pending)->orderBy('i.updated', 'DESC'))];
        }

        if (in_array(self::INVOICE_OVERDUE, $types, true)) {
            $entries = [...$entries, ...$this->invoices(self::INVOICE_OVERDUE, $this->invoiceQuery($take, $client)->andWhere('i.status = :status')->setParameter('status', InvoiceStatus::Overdue)->orderBy('i.due', 'DESC'))];
        }

        if (in_array(self::RECURRING_GENERATED, $types, true)) {
            $entries = [...$entries, ...$this->invoices(self::RECURRING_GENERATED, $this->invoiceQuery($take, $client)->innerJoin('i.recurringInvoice', 'ri')->orderBy('i.created', 'DESC'))];
        }

        foreach ([self::QUOTE_SENT => QuoteStatus::Pending, self::QUOTE_ACCEPTED => QuoteStatus::Accepted, self::QUOTE_DECLINED => QuoteStatus::Declined] as $type => $status) {
            if (in_array($type, $types, true)) {
                $entries = [...$entries, ...$this->quotes($type, $status, $take, $client)];
            }
        }

        if (in_array(self::CLIENT_CREATED, $types, true)) {
            $entries = [...$entries, ...$this->clients($take, $client)];
        }

        usort($entries, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return array_slice($entries, $offset, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws MathException
     */
    private function payments(int $limit, ?Client $client): array
    {
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('p.id', 'p.totalAmount', 'p.currencyCode', 'p.created', 'p.completed', 'p.status', 'i.id AS invoice_ulid', 'i.invoiceId AS invoice', 'm.name AS method', 'c.name AS client', 'c.id AS client_id')
            ->join('p.method', 'm')
            ->join('p.invoice', 'i')
            ->join('p.client', 'c')
            ->where('p.status = :captured')
            ->setParameter('captured', PaymentStatus::Captured->value)
            ->orderBy('p.completed', 'DESC')
            ->addOrderBy('p.created', 'DESC')
            ->setMaxResults($limit);

        if ($client instanceof Client) {
            $qb->andWhere('p.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $entries = [];

        foreach ($qb->getQuery()->getArrayResult() as $payment) {
            $entries[] = [
                'type' => self::PAYMENT,
                'date' => $payment['completed'] ?? $payment['created'],
                'id' => $payment['id'],
                'invoiceId' => $payment['invoice'],
                'invoiceUlid' => $payment['invoice_ulid'],
                'client' => $payment['client'],
                'clientId' => $payment['client_id'],
                'amount' => BigInteger::of($payment['totalAmount']),
                'currency' => $payment['currencyCode'],
                'status' => $payment['status'],
                'method' => $payment['method'],
            ];
        }

        return $entries;
    }

    private function invoiceQuery(int $limit, ?Client $client): QueryBuilder
    {
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->innerJoin('i.client', 'c')
            ->addSelect('c')
            ->setMaxResults($limit);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $qb;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function invoices(string $type, QueryBuilder $qb): array
    {
        $entries = [];

        /** @var Invoice $invoice */
        foreach ($qb->getQuery()->getResult() as $invoice) {
            $date = match ($type) {
                self::RECURRING_GENERATED => $invoice->getCreated(),
                // It became overdue the day after it was due.
                self::INVOICE_OVERDUE => $invoice->getDue() instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($invoice->getDue())->modify('+1 day') : $invoice->getUpdated(),
                default => $invoice->getUpdated() ?? $invoice->getCreated(),
            };

            $entries[] = [
                'type' => $type,
                'date' => $date,
                'id' => $invoice->getId(),
                'invoiceId' => $invoice->getInvoiceId(),
                'client' => $invoice->getClient()?->getName(),
                'clientId' => $invoice->getClient()?->getId(),
                'amount' => $type === self::INVOICE_OVERDUE ? $invoice->getBalance() : $invoice->getTotal(),
                'currency' => $invoice->getCurrency(),
                'status' => $invoice->getStatus(),
            ];
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quotes(string $type, QuoteStatus $status, int $limit, ?Client $client): array
    {
        $qb = $this->quoteRepository->createQueryBuilder('q')
            ->innerJoin('q.client', 'c')
            ->addSelect('c')
            ->where('q.status = :status')
            ->setParameter('status', $status)
            ->orderBy('q.updated', 'DESC')
            ->setMaxResults($limit);

        if ($client instanceof Client) {
            $qb->andWhere('q.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $entries = [];

        /** @var Quote $quote */
        foreach ($qb->getQuery()->getResult() as $quote) {
            $entries[] = [
                'type' => $type,
                'date' => $quote->getUpdated() ?? $quote->getCreated(),
                'id' => $quote->getId(),
                'quoteId' => $quote->getQuoteId(),
                'client' => $quote->getClient()?->getName(),
                'clientId' => $quote->getClient()?->getId(),
                'amount' => $quote->getTotal(),
                'currency' => $quote->getCurrency(),
                'status' => $quote->getStatus(),
            ];
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function clients(int $limit, ?Client $client): array
    {
        $qb = $this->clientRepository->createQueryBuilder('c')
            ->orderBy('c.created', 'DESC')
            ->setMaxResults($limit);

        if ($client instanceof Client) {
            $qb->andWhere('c.id = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $entries = [];

        /** @var Client $created */
        foreach ($qb->getQuery()->getResult() as $created) {
            $entries[] = [
                'type' => self::CLIENT_CREATED,
                'date' => $created->getCreated(),
                'id' => $created->getId(),
                'client' => $created->getName(),
                'clientId' => $created->getId(),
            ];
        }

        return $entries;
    }
}
