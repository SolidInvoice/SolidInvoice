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

namespace SolidInvoice\TaxBundle\Tests\Twig\Breakdown;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Twig\Breakdown\RenderedTaxSummaryRow;
use function sprintf;

/**
 * Pins the SOL-560 fix: the printed taxable base and the printed tax amount on an
 * {@see TaxType::Inclusive} row must always add up to the line gross, even though each
 * is individually rounded from sub-minor-unit precision.
 */
#[CoversClass(RenderedTaxSummaryRow::class)]
final class RenderedTaxSummaryRowTest extends TestCase
{
    private CurrencyScale $scale;

    protected function setUp(): void
    {
        $this->scale = new CurrencyScale();
    }

    /**
     * @param int<0, max> $grossMinorUnits
     */
    #[DataProvider('inclusiveGrossProvider')]
    public function testInclusivePrintedPairSumsToTheLineGross(int $grossMinorUnits, string $rate): void
    {
        $row = $this->inclusiveSummaryRow($grossMinorUnits, $rate);
        $rendered = RenderedTaxSummaryRow::fromSummaryRow($row, $this->scale);

        self::assertTrue(
            $rendered->taxableBase->plus($rendered->amount)->isEqualTo(BigDecimal::of($grossMinorUnits)),
            sprintf('Printed base (%s) + printed tax (%s) must equal the gross (%d).', $rendered->taxableBase, $rendered->amount, $grossMinorUnits)
        );
    }

    #[DataProvider('inclusiveGrossProvider')]
    public function testPrintedBaseStaysWithinOneMinorUnitOfTheExactBase(int $grossMinorUnits, string $rate): void
    {
        $row = $this->inclusiveSummaryRow($grossMinorUnits, $rate);
        $rendered = RenderedTaxSummaryRow::fromSummaryRow($row, $this->scale);

        $deviation = $rendered->taxableBase->minus($rendered->exactTaxableBase)->abs();

        self::assertTrue($deviation->isLessThan(BigDecimal::of(1)));
    }

    public function testExactTaxableBaseIsNotRounded(): void
    {
        // 999.99 gross at 20% inclusive: exact base is 83332.50, which must survive
        // untouched on $exactTaxableBase - only $taxableBase (the printed figure) rounds.
        $row = $this->inclusiveSummaryRow(99999, '20.0000');
        $rendered = RenderedTaxSummaryRow::fromSummaryRow($row, $this->scale);

        self::assertTrue($rendered->exactTaxableBase->isEqualTo(BigDecimal::of('83332.50')));
        self::assertTrue($rendered->exactTaxableBase->isEqualTo($row->taxableBase));
    }

    /**
     * The regression test for the naive `round(base + amount) - round(amount)` form: it
     * moves the printed base on a row whose amount was already a whole number of minor
     * units, which the fix must not do. 277.50 is deliberately a half-even tie, and 55 is
     * deliberately odd, because that combination is exactly where the naive form disagrees
     * with `round(exactBase)` (332.50 rounds to 332, 332 - 55 = 277 != 278).
     */
    #[DataProvider('wholeAmountRowProvider')]
    public function testRowsWithWholeMinorUnitAmountsPrintTheSameBaseAsBefore(TaxSummaryRow $row): void
    {
        $rendered = RenderedTaxSummaryRow::fromSummaryRow($row, $this->scale);

        self::assertTrue($rendered->amount->isEqualTo($row->amount));
        self::assertTrue($rendered->taxableBase->isEqualTo(BigDecimal::of(278)));
    }

    public function testZeroDecimalCurrencyRow(): void
    {
        // JPY has no minor-unit subdivision, but the derivation takes no currency
        // argument - it operates on whatever is already a count of minor units.
        $row = $this->inclusiveSummaryRow(1000, '10.0000');
        $rendered = RenderedTaxSummaryRow::fromSummaryRow($row, $this->scale);

        self::assertTrue($rendered->taxableBase->plus($rendered->amount)->isEqualTo(BigDecimal::of(1000)));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function inclusiveGrossProvider(): iterable
    {
        yield '9.99 @ 20%' => [999, '20.0000'];
        yield '99.99 @ 20%' => [9999, '20.0000'];
        yield '999.99 @ 20%' => [99999, '20.0000'];
        yield '3.33 @ 20%' => [333, '20.0000'];
        yield '123.45 @ 20%' => [12345, '20.0000'];
        yield '99.99 @ 19%' => [9999, '19.0000'];
        yield '99.99 @ 23%' => [9999, '23.0000'];
    }

    /**
     * @return iterable<string, array{TaxSummaryRow}>
     */
    public static function wholeAmountRowProvider(): iterable
    {
        yield 'Exclusive' => [self::summaryRow(TaxType::Exclusive, TaxCategory::Standard)];
        yield 'FlatRate' => [self::summaryRow(TaxType::FlatRate, TaxCategory::Standard)];
        yield 'ZeroRated' => [self::summaryRow(TaxType::Exclusive, TaxCategory::ZeroRated)];
        yield 'Informational' => [self::summaryRow(TaxType::Exclusive, TaxCategory::Standard, TaxDirection::Informational)];
    }

    private static function summaryRow(TaxType $type, TaxCategory $category, TaxDirection $direction = TaxDirection::Additive): TaxSummaryRow
    {
        return new TaxSummaryRow(
            name: 'VAT',
            rate: '20.0000',
            category: $category,
            type: $type,
            compound: false,
            amount: BigDecimal::of(55),
            taxableBase: BigDecimal::of('277.50'),
            sequence: 0,
            direction: $direction,
            note: $direction === TaxDirection::Informational ? 'Reverse charge applies' : null,
        );
    }

    private function inclusiveSummaryRow(int $grossMinorUnits, string $rate): TaxSummaryRow
    {
        $calculator = new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator());

        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice($grossMinorUnits);
        $line->setQty(1);
        $line->addTax($this->lineTax('VAT', $rate));
        $invoice->addLine($line);

        $result = $calculator->calculate($invoice);

        return $result->summaryRows[0];
    }

    private function lineTax(string $name, string $rate): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot($name);
        $lineTax->setRateSnapshot($rate);
        $lineTax->setTypeSnapshot(TaxType::Inclusive);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);

        return $lineTax;
    }
}
