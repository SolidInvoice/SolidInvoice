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
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Model\Allowance;
use SolidInvoice\EInvoiceBundle\Model\Charge;
use SolidInvoice\EInvoiceBundle\Model\Identifier;
use SolidInvoice\EInvoiceBundle\Model\InvoiceLine;
use SolidInvoice\EInvoiceBundle\Model\InvoicingPeriod;
use SolidInvoice\EInvoiceBundle\Model\ItemInformation;
use SolidInvoice\EInvoiceBundle\Model\LineVatInformation;
use SolidInvoice\EInvoiceBundle\Model\PriceDetails;

#[CoversClass(InvoiceLine::class)]
final class InvoiceLineTest extends TestCase
{
    private function createLine(): InvoiceLine
    {
        return new InvoiceLine(
            identifier: '1',
            invoicedQuantity: BigDecimal::of('2'),
            netAmount: BigDecimal::of('180.00'),
            price: new PriceDetails(BigDecimal::of('90.00')),
            vat: new LineVatInformation(VatCategoryCode::StandardRate, BigDecimal::of('15')),
            item: new ItemInformation('Consulting services'),
        );
    }

    public function testConstructionWithAllProperties(): void
    {
        $line = new InvoiceLine(
            identifier: '1',
            invoicedQuantity: BigDecimal::of('2'),
            netAmount: BigDecimal::of('180.00'),
            price: new PriceDetails(BigDecimal::of('90.00')),
            vat: new LineVatInformation(VatCategoryCode::StandardRate, BigDecimal::of('15')),
            item: new ItemInformation('Consulting services'),
            note: 'Discounted rate applied',
            objectIdentifier: new Identifier('METER-1'),
            unitOfMeasureCode: 'DAY',
            orderLineReference: 'PO-1',
            buyerAccountingReference: 'ACC-1',
            invoicingPeriod: new InvoicingPeriod(),
            allowances: [new Allowance(BigDecimal::of('5.00'))],
            charges: [new Charge(BigDecimal::of('2.00'))],
        );

        self::assertSame('1', $line->identifier);
        self::assertTrue($line->invoicedQuantity->isEqualTo(BigDecimal::of('2')));
        self::assertTrue($line->netAmount->isEqualTo(BigDecimal::of('180.00')));
        self::assertSame('Discounted rate applied', $line->note);
        self::assertSame('METER-1', $line->objectIdentifier?->value);
        self::assertSame('DAY', $line->unitOfMeasureCode);
        self::assertSame('PO-1', $line->orderLineReference);
        self::assertSame('ACC-1', $line->buyerAccountingReference);
        self::assertNotNull($line->invoicingPeriod);
        self::assertCount(1, $line->allowances);
        self::assertCount(1, $line->charges);
    }

    public function testOptionalPropertiesDefaultToNullOrEmpty(): void
    {
        $line = $this->createLine();

        self::assertNull($line->note);
        self::assertNull($line->objectIdentifier);
        self::assertNull($line->unitOfMeasureCode);
        self::assertNull($line->orderLineReference);
        self::assertNull($line->buyerAccountingReference);
        self::assertNull($line->invoicingPeriod);
        self::assertSame([], $line->allowances);
        self::assertSame([], $line->charges);
    }

    public function testPropertiesAreReadonly(): void
    {
        $line = $this->createLine();

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $line->identifier = '2';
    }
}
