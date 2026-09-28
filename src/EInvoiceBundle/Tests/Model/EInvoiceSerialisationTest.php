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

use const JSON_THROW_ON_ERROR;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\EInvoiceBundle\Enum\PaymentMeansCode;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Model\AdditionalSupportingDocument;
use SolidInvoice\EInvoiceBundle\Model\Allowance;
use SolidInvoice\EInvoiceBundle\Model\Buyer;
use SolidInvoice\EInvoiceBundle\Model\CardInformation;
use SolidInvoice\EInvoiceBundle\Model\Charge;
use SolidInvoice\EInvoiceBundle\Model\Contact;
use SolidInvoice\EInvoiceBundle\Model\CreditTransfer;
use SolidInvoice\EInvoiceBundle\Model\DeliveryInformation;
use SolidInvoice\EInvoiceBundle\Model\DirectDebit;
use SolidInvoice\EInvoiceBundle\Model\DocumentTotals;
use SolidInvoice\EInvoiceBundle\Model\EInvoice;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\InvoiceLine;
use SolidInvoice\EInvoiceBundle\Model\InvoiceNote;
use SolidInvoice\EInvoiceBundle\Model\InvoicingPeriod;
use SolidInvoice\EInvoiceBundle\Model\ItemAttribute;
use SolidInvoice\EInvoiceBundle\Model\ItemInformation;
use SolidInvoice\EInvoiceBundle\Model\LineVatInformation;
use SolidInvoice\EInvoiceBundle\Model\Payee;
use SolidInvoice\EInvoiceBundle\Model\PaymentInstructions;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;
use SolidInvoice\EInvoiceBundle\Model\PrecedingInvoiceReference;
use SolidInvoice\EInvoiceBundle\Model\PriceDetails;
use SolidInvoice\EInvoiceBundle\Model\Seller;
use SolidInvoice\EInvoiceBundle\Model\SellerTaxRepresentative;
use SolidInvoice\EInvoiceBundle\Model\VatBreakdown;
use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

/**
 * Every downstream stage of the pipeline (validation, syntax writing, storage) needs to be able
 * to round-trip an `EInvoice`. This builds one populated instance of every business group and
 * proves both PHP native serialisation and `json_encode()` survive it without a custom normalizer.
 */
final class EInvoiceSerialisationTest extends TestCase
{
    private function createFullyPopulatedInvoice(): EInvoice
    {
        $address = new PostalAddress('1 Example Street', 'Suite 2', 'Building 3', 'Cape Town', '8001', 'Western Cape', 'ZA');
        $contact = new Contact('Jane Doe', '+27 21 555 0100', 'jane@example.com');
        $period = new InvoicingPeriod(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-01-31'));

        $line = new InvoiceLine(
            identifier: '1',
            invoicedQuantity: BigDecimal::of('2'),
            netAmount: BigDecimal::of('180.00'),
            price: new PriceDetails(BigDecimal::of('100.00'), BigDecimal::of('10.00'), BigDecimal::of('110.00'), BigDecimal::of('1'), 'C62'),
            vat: new LineVatInformation(VatCategoryCode::StandardRate, BigDecimal::of('15')),
            item: new ItemInformation(
                'Consulting services',
                'One day of consulting',
                new Identifier('SKU-1'),
                new Identifier('PO-ITEM-1'),
                new Identifier('05012345678900', '0160'),
                new Identifier('12345', '9', '1.0'),
                'ZA',
                [new ItemAttribute('Colour', 'Red')],
            ),
            note: 'Discounted rate applied',
            objectIdentifier: new Identifier('METER-1'),
            unitOfMeasureCode: 'DAY',
            orderLineReference: 'PO-1',
            buyerAccountingReference: 'ACC-1',
            invoicingPeriod: $period,
            allowances: [new Allowance(BigDecimal::of('5.00'))],
            charges: [new Charge(BigDecimal::of('2.00'))],
        );

        return new EInvoice(
            invoiceNumber: 'INV-0001',
            issueDate: CarbonImmutable::parse('2026-01-01'),
            invoiceTypeCode: InvoiceTypeCode::CommercialInvoice,
            currencyCode: 'EUR',
            seller: new Seller(
                'ACME Corp',
                'ACME',
                [new Identifier('9506')],
                new Identifier('REG123'),
                'ZA1234567',
                'TAX123',
                'Registered in the Western Cape',
                new Identifier('0088:1234567890', '0088'),
                $address,
                $contact,
            ),
            buyer: new Buyer(
                'Client Ltd',
                'Client',
                new Identifier('9507'),
                new Identifier('REG456'),
                'ZA7654321',
                new Identifier('0088:0987654321', '0088'),
                $address,
                $contact,
            ),
            documentTotals: new DocumentTotals(
                sumOfLineNetAmounts: BigDecimal::of('180.00'),
                totalWithoutVat: BigDecimal::of('180.00'),
                vatAmount: BigDecimal::of('27.00'),
                totalWithVat: BigDecimal::of('207.00'),
                sumOfAllowances: BigDecimal::of('5.00'),
                sumOfCharges: BigDecimal::of('2.00'),
                paidAmount: BigDecimal::of('0.00'),
                amountDueForPayment: BigDecimal::of('207.00'),
            ),
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
            payee: new Payee('Factor Finance Ltd', new Identifier('9508'), new Identifier('REG789')),
            sellerTaxRepresentative: new SellerTaxRepresentative('EU Tax Agent BV', 'NL123456789B01', $address),
            deliveryInformation: new DeliveryInformation('Warehouse 3', new Identifier('LOC-3'), CarbonImmutable::parse('2026-01-15'), $period, $address),
            paymentInstructions: new PaymentInstructions(
                PaymentMeansCode::SepaCreditTransfer,
                'SEPA transfer',
                'INV-0001',
                [new CreditTransfer('GB33BUKB20201555555555', 'ACME Corp', 'BUKBGB22')],
                new CardInformation('1234', 'Jane Doe'),
                new DirectDebit('MANDATE-1', 'ZA98ZZZ123456789', 'GB33BUKB20201555555555'),
            ),
            notes: [new InvoiceNote('Thank you for your business', '3')],
            precedingInvoiceReferences: [new PrecedingInvoiceReference('INV-0000', CarbonImmutable::parse('2025-12-01'))],
            allowances: [new Allowance(BigDecimal::of('5.00'), BigDecimal::of('100.00'), BigDecimal::of('5'), VatCategoryCode::StandardRate, BigDecimal::of('15'), 'Loyalty discount', '95')],
            charges: [new Charge(BigDecimal::of('2.00'), BigDecimal::of('100.00'), BigDecimal::of('2'), VatCategoryCode::StandardRate, BigDecimal::of('15'), 'Freight', 'FC')],
            vatBreakdowns: [new VatBreakdown(BigDecimal::of('27.00'), VatCategoryCode::StandardRate, BigDecimal::of('180.00'), BigDecimal::of('15'))],
            additionalSupportingDocuments: [new AdditionalSupportingDocument('DOC-1', 'Timesheet', 'https://example.com/doc-1', null, null, null)],
            invoiceLines: [$line],
        );
    }

    public function testSerializeUnserializeRoundTrip(): void
    {
        $invoice = $this->createFullyPopulatedInvoice();

        $roundTripped = unserialize(serialize($invoice));

        self::assertEquals($invoice, $roundTripped);
    }

    public function testJsonEncodeDoesNotThrow(): void
    {
        $json = json_encode($this->createFullyPopulatedInvoice(), JSON_THROW_ON_ERROR);

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('INV-0001', $decoded['invoiceNumber']);
    }

    public function testTwoModelsBuiltFromTheSameInputsAreEqual(): void
    {
        self::assertEquals($this->createFullyPopulatedInvoice(), $this->createFullyPopulatedInvoice());
    }
}
