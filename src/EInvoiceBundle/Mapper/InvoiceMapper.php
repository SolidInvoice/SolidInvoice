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

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\EInvoiceBundle\Model\DocumentTotals;
use SolidInvoice\EInvoiceBundle\Model\EInvoice;
use SolidInvoice\EInvoiceBundle\Model\InvoiceNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * Maps an {@see Invoice} onto the EN 16931 semantic model. This issue (SOL-84 3/4) populates the
 * header terms, the seller and the buyer; lines, allowances, the VAT breakdown and the BT
 * coverage matrix follow in SOL-84 4/4.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\InvoiceMapperTest
 */
final readonly class InvoiceMapper
{
    public function __construct(
        private SellerMapper $sellerMapper,
        private BuyerMapper $buyerMapper,
        private MinorUnitConverter $converter,
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

        return new EInvoice(
            invoiceNumber: $invoice->getInvoiceId(),
            issueDate: DateTimeImmutable::createFromInterface($invoice->getInvoiceDate()),
            invoiceTypeCode: $invoice->getInvoiceTypeCode(),
            currencyCode: $currency->getCode(),
            seller: $this->sellerMapper->map(),
            buyer: $this->buyerMapper->map($client, $invoice->getUsers()),
            documentTotals: $this->mapDocumentTotals($invoice, $currency),
            dueDate: $invoice->getDue() instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($invoice->getDue()) : null,
            paymentTerms: $invoice->getTerms(),
            notes: null !== $notes && '' !== $notes ? [new InvoiceNote($notes)] : [],
        );
    }

    /**
     * BT-106/109/110/112/113/115. Read from the entity's already-persisted total/tax/baseTotal
     * fields rather than calling `TaxCalculatorInterface` afresh: `CalculationResult` does not
     * carry the document-level discount (`TotalCalculator` applies that separately, only to the
     * persisted total), so deriving BT-112 from it directly would disagree with the amount the
     * invoice actually shows whenever a discount is set. Reading the persisted fields is the
     * design's own delegation principle (§13.1) applied one step further: one implementation
     * already combined tax and discount, and this reads its result instead of a second one.
     * BT-107 (allowances), BT-108 (charges), BT-111, BT-114 stay null — not sourced today.
     */
    private function mapDocumentTotals(Invoice $invoice, Currency $currency): DocumentTotals
    {
        $total = $invoice->getTotal()->toBigDecimal();
        $tax = $invoice->getTax()->toBigDecimal();
        $balance = $invoice->getBalance()->toBigDecimal();

        return new DocumentTotals(
            sumOfLineNetAmounts: $this->converter->toMajorUnit($invoice->getBaseTotal(), $currency),
            totalWithoutVat: $this->converter->toMajorUnit($total->minus($tax), $currency),
            vatAmount: $this->converter->toMajorUnit($tax, $currency),
            totalWithVat: $this->converter->toMajorUnit($total, $currency),
            paidAmount: $this->converter->toMajorUnit($total->minus($balance), $currency),
            amountDueForPayment: $this->converter->toMajorUnit($balance, $currency),
        );
    }
}
