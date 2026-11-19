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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\EInvoiceBundle\Model\Buyer;
use SolidInvoice\EInvoiceBundle\Model\DocumentTotals;
use SolidInvoice\EInvoiceBundle\Model\EInvoice;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\InvoiceNote;
use SolidInvoice\EInvoiceBundle\Model\Payee;
use SolidInvoice\EInvoiceBundle\Model\Seller;
use SolidInvoice\EInvoiceBundle\Model\SellerTaxRepresentative;

#[CoversClass(EInvoice::class)]
final class EInvoiceTest extends TestCase
{
    private function createDocumentTotals(): DocumentTotals
    {
        return new DocumentTotals(
            sumOfLineNetAmounts: BigDecimal::of('100.00'),
            totalWithoutVat: BigDecimal::of('100.00'),
            vatAmount: BigDecimal::of('15.00'),
            totalWithVat: BigDecimal::of('115.00'),
        );
    }

    private function createMinimalInvoice(): EInvoice
    {
        return new EInvoice(
            invoiceNumber: 'INV-0001',
            issueDate: CarbonImmutable::parse('2026-01-01'),
            invoiceTypeCode: InvoiceTypeCode::CommercialInvoice,
            currencyCode: 'EUR',
            seller: new Seller('ACME Corp'),
            buyer: new Buyer('Client Ltd'),
            documentTotals: $this->createDocumentTotals(),
        );
    }

    public function testConstructionWithAllRootProperties(): void
    {
        $invoice = new EInvoice(
            invoiceNumber: 'INV-0001',
            issueDate: CarbonImmutable::parse('2026-01-01'),
            invoiceTypeCode: InvoiceTypeCode::CommercialInvoice,
            currencyCode: 'EUR',
            seller: new Seller('ACME Corp'),
            buyer: new Buyer('Client Ltd'),
            documentTotals: $this->createDocumentTotals(),
            vatAccountingCurrencyCode: 'USD',
            taxPointDate: CarbonImmutable::parse('2026-01-02'),
            vatPointDateCode: '3',
            dueDate: CarbonImmutable::parse('2026-01-31'),
            buyerReference: 'BUYER-REF',
            projectReference: 'PROJ-1',
            contractReference: 'CONTRACT-1',
            purchaseOrderReference: 'PO-1',
            salesOrderReference: 'SO-1',
            receivingAdviceReference: 'RA-1',
            despatchAdviceReference: 'DA-1',
            tenderOrLotReference: 'LOT-1',
            invoicedObjectIdentifier: new Identifier('METER-1'),
            buyerAccountingReference: 'ACC-1',
            paymentTerms: 'Net 30',
            businessProcessType: 'urn:process',
            specificationIdentifier: 'urn:cen.eu:en16931:2017',
            payee: new Payee('Factor Finance Ltd'),
            sellerTaxRepresentative: new SellerTaxRepresentative('EU Tax Agent BV'),
            notes: [new InvoiceNote('Thank you for your business')],
        );

        self::assertSame('INV-0001', $invoice->invoiceNumber);
        self::assertSame('2026-01-01', $invoice->issueDate->format('Y-m-d'));
        self::assertSame(InvoiceTypeCode::CommercialInvoice, $invoice->invoiceTypeCode);
        self::assertSame('EUR', $invoice->currencyCode);
        self::assertSame('ACME Corp', $invoice->seller->name);
        self::assertSame('Client Ltd', $invoice->buyer->name);
        self::assertSame('USD', $invoice->vatAccountingCurrencyCode);
        self::assertSame('2026-01-02', $invoice->taxPointDate?->format('Y-m-d'));
        self::assertSame('3', $invoice->vatPointDateCode);
        self::assertSame('2026-01-31', $invoice->dueDate?->format('Y-m-d'));
        self::assertSame('BUYER-REF', $invoice->buyerReference);
        self::assertSame('PROJ-1', $invoice->projectReference);
        self::assertSame('CONTRACT-1', $invoice->contractReference);
        self::assertSame('PO-1', $invoice->purchaseOrderReference);
        self::assertSame('SO-1', $invoice->salesOrderReference);
        self::assertSame('RA-1', $invoice->receivingAdviceReference);
        self::assertSame('DA-1', $invoice->despatchAdviceReference);
        self::assertSame('LOT-1', $invoice->tenderOrLotReference);
        self::assertSame('METER-1', $invoice->invoicedObjectIdentifier?->value);
        self::assertSame('ACC-1', $invoice->buyerAccountingReference);
        self::assertSame('Net 30', $invoice->paymentTerms);
        self::assertSame('urn:process', $invoice->businessProcessType);
        self::assertSame('urn:cen.eu:en16931:2017', $invoice->specificationIdentifier);
        self::assertSame('Factor Finance Ltd', $invoice->payee?->name);
        self::assertSame('EU Tax Agent BV', $invoice->sellerTaxRepresentative?->name);
        self::assertCount(1, $invoice->notes);
    }

    public function testOptionalPropertiesDefaultToNullOrEmpty(): void
    {
        $invoice = $this->createMinimalInvoice();

        self::assertNull($invoice->vatAccountingCurrencyCode);
        self::assertNull($invoice->taxPointDate);
        self::assertNull($invoice->vatPointDateCode);
        self::assertNull($invoice->dueDate);
        self::assertNull($invoice->buyerReference);
        self::assertNull($invoice->invoicedObjectIdentifier);
        self::assertNull($invoice->payee);
        self::assertNull($invoice->sellerTaxRepresentative);
        self::assertNull($invoice->deliveryInformation);
        self::assertNull($invoice->paymentInstructions);
        self::assertSame([], $invoice->notes);
        self::assertSame([], $invoice->precedingInvoiceReferences);
        self::assertSame([], $invoice->allowances);
        self::assertSame([], $invoice->charges);
        self::assertSame([], $invoice->vatBreakdowns);
        self::assertSame([], $invoice->additionalSupportingDocuments);
        self::assertSame([], $invoice->invoiceLines);
    }

    public function testPropertiesAreReadonly(): void
    {
        $invoice = $this->createMinimalInvoice();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $invoice->invoiceNumber = 'INV-0002';
    }
}
