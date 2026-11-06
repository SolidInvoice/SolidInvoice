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
use Money\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\EInvoiceBundle\Mapper\VatBreakdownMapper;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;

final class VatBreakdownMapperTest extends TestCase
{
    private VatBreakdownMapper $mapper;

    private Currency $currency;

    protected function setUp(): void
    {
        $this->mapper = new VatBreakdownMapper(new MinorUnitConverter(new CurrencyScale()));
        $this->currency = new Currency('EUR');
    }

    public function testOneEntryPerCategoryRateCombination(): void
    {
        $rows = [
            $this->row(name: 'VAT', rate: '20.0000', category: TaxCategory::Standard, amount: 2000),
            $this->row(name: 'VAT', rate: '9.0000', category: TaxCategory::ZeroRated, amount: 0),
        ];

        $breakdowns = $this->mapper->map($rows, $this->currency);

        self::assertCount(2, $breakdowns);
        self::assertSame('20.00', (string) $breakdowns[0]->taxAmount);
        self::assertSame(VatCategoryCode::StandardRate, $breakdowns[0]->categoryCode);
        self::assertSame('20.0000', (string) $breakdowns[0]->rate);
        self::assertSame('0.00', (string) $breakdowns[1]->taxAmount);
        self::assertSame(VatCategoryCode::ZeroRated, $breakdowns[1]->categoryCode);
    }

    /**
     * @return iterable<string, array{TaxCategory, VatCategoryCode}>
     */
    public static function categoryMappings(): iterable
    {
        yield 'standard' => [TaxCategory::Standard, VatCategoryCode::StandardRate];
        yield 'zero-rated' => [TaxCategory::ZeroRated, VatCategoryCode::ZeroRated];
        yield 'exempt' => [TaxCategory::Exempt, VatCategoryCode::Exempt];
        yield 'out-of-scope' => [TaxCategory::OutOfScope, VatCategoryCode::OutsideScope];
        yield 'reverse-charge' => [TaxCategory::ReverseCharge, VatCategoryCode::ReverseCharge];
    }

    #[DataProvider('categoryMappings')]
    public function testEachTaxCategoryMapsToTheRightVatCategoryCode(TaxCategory $taxCategory, VatCategoryCode $expected): void
    {
        $breakdowns = $this->mapper->map([$this->row(category: $taxCategory)], $this->currency);

        self::assertSame($expected, $breakdowns[0]->categoryCode);
    }

    public function testTaxableAmountIsNullForACompoundRow(): void
    {
        $breakdowns = $this->mapper->map([$this->row(compound: true)], $this->currency);

        self::assertNull($breakdowns[0]->taxableAmount);
    }

    /**
     * BT-116 is null even for a plain, non-compound row: {@see TaxSummaryRow} never carries a
     * taxable base, for any row — not only the compound/document-level cases design §5.5 names.
     * Flagged on SOL-84; this is the stronger, currently-correct version of that rule.
     */
    public function testTaxableAmountIsNullForANonCompoundRowToo(): void
    {
        $breakdowns = $this->mapper->map([$this->row(compound: false)], $this->currency);

        self::assertNull($breakdowns[0]->taxableAmount);
    }

    private function row(
        string $name = 'VAT',
        string $rate = '20.0000',
        TaxCategory $category = TaxCategory::Standard,
        TaxType $type = TaxType::Exclusive,
        bool $compound = false,
        int $amount = 0,
    ): TaxSummaryRow {
        return new TaxSummaryRow(
            name: $name,
            rate: $rate,
            category: $category,
            type: $type,
            compound: $compound,
            amount: BigDecimal::of($amount),
        );
    }
}
