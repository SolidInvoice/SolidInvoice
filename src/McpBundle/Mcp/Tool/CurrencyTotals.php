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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Brick\Math\BigInteger;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Output\AmountOutput;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Types\UlidType;
use function ksort;

/**
 * Money totals for the MCP analytics tools, one per currency. A client's invoices are not all in
 * one currency (an issued invoice keeps the currency it was issued in when the client's changes),
 * so invoices are grouped by their own currency, falling back to the client's for a draft.
 */
final readonly class CurrencyTotals
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private EntityNormalizer $normalizer,
        private SystemConfig $config,
    ) {
    }

    /**
     * @param 'total'|'balance'   $field
     * @param list<InvoiceStatus> $statuses only invoices in these statuses; every invoice when empty
     *
     * @return list<AmountOutput>
     */
    public function invoices(string $field, array $statuses = [], ?Client $client = null): array
    {
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->select('COALESCE(i.currencyCode, c.currencyCode) AS currency', 'SUM(i.' . $field . ') AS amount')
            ->innerJoin('i.client', 'c')
            ->groupBy('currency');

        if ($statuses !== []) {
            $qb->andWhere('i.status IN (:statuses)')->setParameter('statuses', $statuses);
        }

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->amounts($qb->getQuery()->getArrayResult());
    }

    /**
     * @return list<AmountOutput>
     */
    public function capturedPayments(Client $client): array
    {
        $rows = $this->paymentRepository->createQueryBuilder('p')
            ->select('p.currencyCode AS currency', 'SUM(p.totalAmount) AS amount')
            ->andWhere('p.client = :client AND p.status = :status')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('status', PaymentStatus::Captured->value)
            ->groupBy('currency')
            ->getQuery()
            ->getArrayResult();

        return $this->amounts($rows);
    }

    /**
     * Sums minor-unit amounts per currency and converts each sum to a decimal string.
     *
     * @param iterable<array{currency: ?string, amount: int|string|null}> $rows
     *
     * @return list<AmountOutput>
     */
    public function amounts(iterable $rows): array
    {
        $sums = [];

        foreach ($rows as $row) {
            if ($row['amount'] === null) {
                continue;
            }

            // A record with no currency of its own or via its client uses the system default, as everywhere else.
            $currency = $row['currency'] === null || $row['currency'] === '' ? $this->config->getCurrency()->getCode() : $row['currency'];
            $sums[$currency] = ($sums[$currency] ?? BigInteger::zero())->plus(BigInteger::of($row['amount']));
        }

        ksort($sums);

        $result = [];

        foreach ($sums as $currency => $sum) {
            $result[] = new AmountOutput((string) $currency, $this->normalizer->money($sum, (string) $currency));
        }

        return $result;
    }
}
