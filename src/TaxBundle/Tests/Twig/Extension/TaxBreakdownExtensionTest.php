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

namespace SolidInvoice\TaxBundle\Tests\Twig\Extension;

use Brick\Math\BigDecimal;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\Result\CalculationResult;
use SolidInvoice\TaxBundle\Calculator\Result\InvoiceLevelBreakdown;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use SolidInvoice\TaxBundle\Twig\Breakdown\RenderedTaxSummaryRow;
use SolidInvoice\TaxBundle\Twig\Extension\TaxBreakdownExtension;

final class TaxBreakdownExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private TaxCalculatorInterface | MockInterface $taxCalculator;

    private TaxBreakdownExtension $extension;

    protected function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->taxCalculator = Mockery::mock(TaxCalculatorInterface::class);

        $this->extension = new TaxBreakdownExtension(
            new TaxIdentifierRepository($registry),
            new CompanySelector($registry),
            $this->taxCalculator,
            new CurrencyScale(),
        );
    }

    /**
     * Every bucket maps its rows through {@see RenderedTaxSummaryRow}, and the
     * line-level/invoice-level split (isInvoiceLevelRow()) still runs on the raw
     * {@see TaxSummaryRow} before mapping, so it keeps landing rows in the right bucket.
     */
    public function testAllFourBucketsMapToRenderedRowsInTheRightBucket(): void
    {
        $lineRow = new TaxSummaryRow(
            name: 'VAT',
            rate: '20.0000',
            category: TaxCategory::Standard,
            type: TaxType::Inclusive,
            compound: false,
            amount: BigDecimal::of(10),
            taxableBase: BigDecimal::of(40),
        );

        $invoiceRow = new TaxSummaryRow(
            name: 'Service Charge',
            rate: '5.0000',
            category: TaxCategory::Standard,
            type: TaxType::Exclusive,
            compound: false,
            amount: BigDecimal::of(5),
            taxableBase: BigDecimal::of(100),
            direction: TaxDirection::Additive,
        );

        $deductiveRow = new TaxSummaryRow(
            name: 'TDS',
            rate: '2.0000',
            category: TaxCategory::Standard,
            type: TaxType::Exclusive,
            compound: false,
            amount: BigDecimal::of(2),
            taxableBase: BigDecimal::of(100),
            direction: TaxDirection::Deductive,
        );

        $informationalRow = new TaxSummaryRow(
            name: 'Reverse Charge',
            rate: '0.0000',
            category: TaxCategory::ReverseCharge,
            type: TaxType::Exclusive,
            compound: false,
            amount: BigDecimal::zero(),
            taxableBase: BigDecimal::of(100),
            direction: TaxDirection::Informational,
            note: 'Customer to account for VAT',
        );

        $result = new CalculationResult(
            subTotal: BigDecimal::of(100),
            totalLineTax: BigDecimal::of(10),
            total: BigDecimal::of(103),
            lineBreakdowns: [],
            invoiceLevelBreakdown: new InvoiceLevelBreakdown(
                totalInvoiceLevelTax: BigDecimal::of(5),
                taxRows: [$invoiceRow, $deductiveRow, $informationalRow],
                totalWithholding: BigDecimal::of(2),
            ),
            summaryRows: [$lineRow, $invoiceRow, $deductiveRow, $informationalRow],
        );

        $invoice = new Invoice();

        $this->taxCalculator
            ->shouldReceive('calculate')
            ->once()
            ->with($invoice)
            ->andReturn($result);

        $breakdown = $this->extension->taxBreakdown($invoice);

        self::assertCount(1, $breakdown['lineTaxRows']);
        self::assertInstanceOf(RenderedTaxSummaryRow::class, $breakdown['lineTaxRows'][0]);
        self::assertSame('VAT', $breakdown['lineTaxRows'][0]->name);

        self::assertCount(1, $breakdown['additiveRows']);
        self::assertInstanceOf(RenderedTaxSummaryRow::class, $breakdown['additiveRows'][0]);
        self::assertSame('Service Charge', $breakdown['additiveRows'][0]->name);

        self::assertCount(1, $breakdown['deductiveRows']);
        self::assertInstanceOf(RenderedTaxSummaryRow::class, $breakdown['deductiveRows'][0]);
        self::assertSame('TDS', $breakdown['deductiveRows'][0]->name);

        self::assertCount(1, $breakdown['informationalRows']);
        self::assertInstanceOf(RenderedTaxSummaryRow::class, $breakdown['informationalRows'][0]);
        self::assertSame('Customer to account for VAT', $breakdown['informationalRows'][0]->note);
    }

    /**
     * R7's merged-row case: three lines at the same inclusive rate merge into one
     * {@see TaxSummaryRow} (see {@see \SolidInvoice\TaxBundle\Tests\Calculator\TaxSummaryRowAggregationTest}),
     * and the printed pair on the merged row must still reconcile to the summed gross.
     */
    public function testMergedRowAtOneRateReconcilesToTheMergedGross(): void
    {
        $calculator = new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator());

        $invoice = new Invoice();
        $grossAmounts = [999, 9999, 99999];

        foreach ($grossAmounts as $gross) {
            $line = new Line();
            $line->setPrice($gross);
            $line->setQty(1);
            $line->addTax($this->lineTax());
            $invoice->addLine($line);
        }

        $this->taxCalculator
            ->shouldReceive('calculate')
            ->once()
            ->with($invoice)
            ->andReturn($calculator->calculate($invoice));

        $breakdown = $this->extension->taxBreakdown($invoice);

        self::assertCount(1, $breakdown['lineTaxRows']);
        $row = $breakdown['lineTaxRows'][0];

        self::assertTrue($row->taxableBase->plus($row->amount)->isEqualTo(BigDecimal::of(array_sum($grossAmounts))));
    }

    private function lineTax(): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');
        $lineTax->setTypeSnapshot(TaxType::Inclusive);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);

        return $lineTax;
    }
}
