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

namespace SolidInvoice\EInvoiceBundle\Mapper;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\EInvoiceBundle\Model\Allowance;
use SolidInvoice\EInvoiceBundle\Model\DocumentTotals;
use SolidInvoice\EInvoiceBundle\Model\EInvoice;
use SolidInvoice\EInvoiceBundle\Model\InvoiceNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;

/**
 * Maps an {@see Invoice} onto the EN 16931 semantic model: header, seller and buyer (SOL-84 3/4),
 * plus lines, the document-level allowance, the VAT breakdown and document totals (SOL-84 4/4).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\InvoiceMapperTest
 */
final readonly class InvoiceMapper
{
    public function __construct(
        private SellerMapper $sellerMapper,
        private BuyerMapper $buyerMapper,
        private InvoiceLineMapper $lineMapper,
        private VatBreakdownMapper $vatBreakdownMapper,
        private MinorUnitConverter $converter,
        private TaxCalculatorInterface $taxCalculator,
        private Calculator $calculator,
        private SystemConfig $systemConfig,
    ) {
    }

    public function map(Invoice $invoice): EInvoice
    {
        $client = $invoice->getClient();

        // Column is NOT NULL and the client is Assert\NotBlank-validated: a null here means
        // corrupt data, not a legitimately clientless invoice. Fail loudly rather than
        // fabricating a buyer.
        if (! $client instanceof Client) {
            throw new LogicException(sprintf('Invoice "%s" has no client; cannot map BG-7.', $invoice->getInvoiceId()));
        }

        $currency = new Currency($client->getCurrencyCode() ?? $this->systemConfig->getCurrency()->getCode());
        $notes = $invoice->getNotes();

        // Read once and reused for both the lines and the VAT breakdown: `$invoice->getLines()`
        // is the same cached Collection the calculator iterates, so the index-aligned zip below
        // with $result->lineBreakdowns is safe (design §5.3/§13.1 — delegate, don't recompute).
        $lines = array_values($invoice->getLines()->toArray());
        $result = $this->taxCalculator->calculate($invoice);

        $invoiceLines = [];
        foreach ($lines as $i => $line) {
            $invoiceLines[] = $this->lineMapper->map($line, $result->lineBreakdowns[$i], $currency);
        }

        $allowances = $this->mapAllowances($invoice, $currency);

        return new EInvoice(
            invoiceNumber: $invoice->getInvoiceId(),
            issueDate: DateTimeImmutable::createFromInterface($invoice->getInvoiceDate()),
            invoiceTypeCode: $invoice->getInvoiceTypeCode(),
            currencyCode: $currency->getCode(),
            seller: $this->sellerMapper->map(),
            buyer: $this->buyerMapper->map($client, $invoice->getUsers()),
            documentTotals: $this->mapDocumentTotals($invoice, $currency, $allowances),
            dueDate: $invoice->getDue() instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($invoice->getDue()) : null,
            paymentTerms: $invoice->getTerms(),
            notes: null !== $notes && '' !== $notes ? [new InvoiceNote($notes)] : [],
            allowances: $allowances,
            vatBreakdowns: $this->vatBreakdownMapper->map($result->summaryRows, $currency),
            invoiceLines: $invoiceLines,
        );
    }

    /**
     * BG-20. One document-level allowance derived from {@see Discount}, present only when a
     * discount is actually set — the same truthiness check `TotalCalculator::updateTotal()` uses
     * before applying one. The amount is `Calculator::calculateDiscount()`'s result, not
     * recomputed here (design §5.4). BT-93 (base amount) is not named in the design's sourcing
     * table, so it stays null rather than being derived; BT-95/96 stay null — the `Discount`
     * embeddable carries no VAT category (#2663 / SOL-91).
     *
     * @return list<Allowance>
     */
    private function mapAllowances(Invoice $invoice, Currency $currency): array
    {
        $discount = $invoice->getDiscount();

        if (! $discount->getValue()) {
            return [];
        }

        $percentage = $discount->getValuePercentage();

        return [
            new Allowance(
                amount: $this->converter->toMajorUnit($this->calculator->calculateDiscount($invoice), $currency),
                percentage: Discount::TYPE_PERCENTAGE === $discount->getType() && null !== $percentage
                    ? BigDecimal::of((string) $percentage)
                    : null,
            ),
        ];
    }

    /**
     * BT-106/109/110/112/113/115. Read from the entity's already-persisted total/tax/baseTotal
     * fields rather than calling `TaxCalculatorInterface` afresh: `CalculationResult` does not
     * carry the document-level discount (`TotalCalculator` applies that separately, only to the
     * persisted total), so deriving BT-112 from it directly would disagree with the amount the
     * invoice actually shows whenever a discount is set. Reading the persisted fields is the
     * design's own delegation principle (§13.1) applied one step further: one implementation
     * already combined tax and discount, and this reads its result instead of a second one.
     * BT-108 (charges), BT-111, BT-114 stay null — not sourced today.
     *
     * @param list<Allowance> $allowances
     */
    private function mapDocumentTotals(Invoice $invoice, Currency $currency, array $allowances): DocumentTotals
    {
        $total = $invoice->getTotal()->toBigDecimal();
        $tax = $invoice->getTax()->toBigDecimal();
        $balance = $invoice->getBalance()->toBigDecimal();

        return new DocumentTotals(
            sumOfLineNetAmounts: $this->converter->toMajorUnit($invoice->getBaseTotal(), $currency),
            totalWithoutVat: $this->converter->toMajorUnit($total->minus($tax), $currency),
            vatAmount: $this->converter->toMajorUnit($tax, $currency),
            totalWithVat: $this->converter->toMajorUnit($total, $currency),
            sumOfAllowances: [] !== $allowances ? array_reduce(
                $allowances,
                static fn (BigDecimal $carry, Allowance $allowance): BigDecimal => $carry->plus($allowance->amount),
                BigDecimal::zero(),
            ) : null,
            paidAmount: $this->converter->toMajorUnit($total->minus($balance), $currency),
            amountDueForPayment: $this->converter->toMajorUnit($balance, $currency),
        );
    }
}
