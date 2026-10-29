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

namespace SolidInvoice\ApiBundle\Action\Reporting;

use ApiPlatform\Metadata\IriConverterInterface;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use DateTimeInterface;
use Money\Currency;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\DashboardBundle\Stats\DashboardStats;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use function array_map;
use function sprintf;

/**
 * Everything the dashboard shows, in one call. Amounts are decimal strings per currency, never
 * summed across currencies. Cached for a minute per company.
 */
#[AsController]
#[Route(path: '/dashboard', name: 'api_dashboard', methods: ['GET'])]
final readonly class Dashboard
{
    private const int TTL = 60;

    private const int ATTENTION_LIMIT = 5;

    private const int EXPIRING_WITHIN_DAYS = 7;

    private const int REVENUE_MONTHS = 6;

    public function __construct(
        private DashboardStats $stats,
        private CurrencyScale $currencyScale,
        private IriConverterInterface $iriConverter,
        private CompanySelector $companySelector,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $data = $this->cache->get(
            sprintf('api_dashboard_%s', $this->companySelector->getCompany()),
            function (ItemInterface $item): array {
                $item->expiresAfter(self::TTL);

                return $this->build();
            },
        );

        $response = new JsonResponse($data);
        $response->setPrivate();
        $response->setMaxAge(self::TTL);

        return $response;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MathException
     */
    private function build(): array
    {
        return [
            'outstanding' => $this->totals($this->stats->outstanding()),
            'overdue' => $this->totals($this->stats->overdue()),
            'paidThisMonth' => $this->totals($this->stats->paidThisMonth()),
            'paidLastMonth' => $this->totals($this->stats->paidLastMonth()),
            'draftInvoices' => $this->stats->invoiceCount(InvoiceStatus::Draft),
            'pendingQuotes' => $this->stats->quoteCount(QuoteStatus::Pending),
            'quotesAcceptedThisMonth' => $this->stats->quotesAcceptedThisMonth(),
            'needsAttention' => [
                ...array_map($this->overdueItem(...), $this->stats->overdueInvoices(self::ATTENTION_LIMIT)),
                ...array_map($this->expiringItem(...), $this->stats->expiringQuotes(self::EXPIRING_WITHIN_DAYS, self::ATTENTION_LIMIT)),
                ...array_map($this->draftItem(...), $this->stats->draftInvoices(self::ATTENTION_LIMIT)),
            ],
            'revenueByMonth' => array_map(
                fn (array $row): array => ['month' => $row['month'], 'currency' => $row['currency'], 'amount' => $this->money($row['amount'], $row['currency'])],
                $this->stats->revenueByMonth(self::REVENUE_MONTHS),
            ),
        ];
    }

    /**
     * @param array<string, array{amount: BigInteger, count: int}> $totals
     *
     * @return list<array{currency: string, amount: string, count: int}>
     *
     * @throws MathException
     */
    private function totals(array $totals): array
    {
        $result = [];

        foreach ($totals as $currency => $total) {
            $result[] = ['currency' => $currency, 'amount' => $this->money($total['amount'], $currency), 'count' => $total['count']];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MathException
     */
    private function overdueItem(Invoice $invoice): array
    {
        $currency = $invoice->getCurrency()->getCode();
        $due = $invoice->getDue();

        return [
            'type' => 'invoice_overdue',
            'iri' => $this->iriConverter->getIriFromResource($invoice),
            'label' => sprintf('%s · %s', $invoice->getInvoiceId(), $invoice->getClient()?->getName()),
            'amount' => $this->money($invoice->getBalance(), $currency),
            'currency' => $currency,
            'daysOverdue' => $due instanceof DateTimeInterface ? (int) $this->stats->today()->diff($due)->days : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expiringItem(Quote $quote): array
    {
        $due = $quote->getDue();

        return [
            'type' => 'quote_expiring',
            'iri' => $this->iriConverter->getIriFromResource($quote),
            'label' => sprintf('%s · %s', $quote->getQuoteId(), $quote->getClient()?->getName()),
            'daysLeft' => $due instanceof DateTimeInterface ? (int) $this->stats->today()->diff($due)->days : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function draftItem(Invoice $invoice): array
    {
        return [
            'type' => 'invoice_draft',
            'iri' => $this->iriConverter->getIriFromResource($invoice),
            'label' => sprintf('%s · %s', $invoice->getInvoiceId(), $invoice->getClient()?->getName()),
        ];
    }

    /**
     * @throws MathException
     */
    private function money(BigNumber $minor, string $currency): string
    {
        return $this->currencyScale->toDecimalString($minor, new Currency($currency));
    }
}
