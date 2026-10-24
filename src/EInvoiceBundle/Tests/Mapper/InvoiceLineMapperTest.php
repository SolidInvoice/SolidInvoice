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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use Brick\Math\BigDecimal;
use LogicException;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceLineMapper;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\TaxBundle\Calculator\Result\LineBreakdown;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;

final class InvoiceLineMapperTest extends TestCase
{
    private InvoiceLineMapper $mapper;

    private Currency $currency;

    protected function setUp(): void
    {
        $this->mapper = new InvoiceLineMapper(new MinorUnitConverter(new CurrencyScale()));
        $this->currency = new Currency('EUR');
    }

    public function testNetAmountIsTheInclusiveExtractedSubtotalForAnInclusiveTax(): void
    {
        // Gross 300.00, 20% inclusive VAT already extracted to a 250.00 net line subtotal.
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(rate: '20.0000', type: TaxType::Inclusive));

        $breakdown = new LineBreakdown(
            lineSubtotal: BigDecimal::of(25000),
            lineTotal: BigDecimal::of(30000),
            lineTax: BigDecimal::of(5000),
            taxRows: [],
        );

        $invoiceLine = $this->mapper->map($line, $breakdown, $this->currency);

        self::assertSame('250.00', (string) $invoiceLine->netAmount);
    }

    public function testNetAmountIsTheGrossForAnExclusiveTax(): void
    {
        // Gross 300.00 stays the line subtotal; 20% exclusive VAT is added on top, not extracted.
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(rate: '20.0000', type: TaxType::Exclusive));

        $breakdown = new LineBreakdown(
            lineSubtotal: BigDecimal::of(30000),
            lineTotal: BigDecimal::of(36000),
            lineTax: BigDecimal::of(6000),
            taxRows: [],
        );

        $invoiceLine = $this->mapper->map($line, $breakdown, $this->currency);

        self::assertSame('300.00', (string) $invoiceLine->netAmount);
    }

    public function testIdentifierIsOneBased(): void
    {
        $line = $this->makeLine(price: 1000, qty: 1, position: 0);
        $line->addTax($this->lineTax(rate: '0.0000', type: TaxType::Exclusive));

        $breakdown = new LineBreakdown(BigDecimal::of(1000), BigDecimal::of(1000), BigDecimal::zero(), []);

        self::assertSame('1', $this->mapper->map($line, $breakdown, $this->currency)->identifier);

        $thirdLine = $this->makeLine(price: 1000, qty: 1, position: 2);
        $thirdLine->addTax($this->lineTax(rate: '0.0000', type: TaxType::Exclusive));

        self::assertSame('3', $this->mapper->map($thirdLine, $breakdown, $this->currency)->identifier);
    }

    public function testVatInformationMapsTheFirstTaxRowBySequence(): void
    {
        $line = $this->makeLine(price: 1000, qty: 1);
        $line->addTax($this->lineTax(rate: '9.0000', type: TaxType::Exclusive, category: TaxCategory::ZeroRated, sequence: 1));
        $line->addTax($this->lineTax(rate: '20.0000', type: TaxType::Exclusive, category: TaxCategory::Standard, sequence: 0));

        $breakdown = new LineBreakdown(BigDecimal::of(1000), BigDecimal::of(1200), BigDecimal::of(200), []);

        $invoiceLine = $this->mapper->map($line, $breakdown, $this->currency);

        self::assertSame(VatCategoryCode::StandardRate, $invoiceLine->vat->categoryCode);
        self::assertSame('20.0000', (string) $invoiceLine->vat->rate);
    }

    public function testALineWithNoTaxFailsLoudlyRatherThanGuessingACategory(): void
    {
        $line = $this->makeLine(price: 1000, qty: 1);
        $breakdown = new LineBreakdown(BigDecimal::of(1000), BigDecimal::of(1000), BigDecimal::zero(), []);

        $this->expectException(LogicException::class);

        $this->mapper->map($line, $breakdown, $this->currency);
    }

    private function makeLine(int $price, int $qty, int $position = 0): Line
    {
        $line = new Line();
        $line->setName('Consulting');
        $line->setPrice($price);
        $line->setQty($qty);
        $line->setPosition($position);
        $line->updateTotal();

        return $line;
    }

    private function lineTax(
        string $rate,
        TaxType $type,
        TaxCategory $category = TaxCategory::Standard,
        int $sequence = 0,
    ): LineTax {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot($rate);
        $lineTax->setTypeSnapshot($type);
        $lineTax->setCategorySnapshot($category);
        $lineTax->setCompound(false);
        $lineTax->setSequence($sequence);

        return $lineTax;
    }
}
