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

namespace SolidInvoice\TaxBundle\Twig\Breakdown;

use Brick\Math\BigDecimal;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * A {@see TaxSummaryRow} shaped for the render path, where {@see $amount} and
 * {@see $taxableBase} are both whole minor units and reconcile with each other:
 * {@see $taxableBase} is derived from the already-rounded {@see $amount}
 * (`gross - printedTax`), not rounded independently of it. That is what keeps the two
 * printed figures adding up to the printed line total on an `Inclusive` tax line (SOL-560).
 *
 * {@see $exactTaxableBase} carries the unrounded value {@see TaxSummaryRow} has, so a test
 * can assert the derivation without reaching back into the calculator result.
 *
 * @see \SolidInvoice\TaxBundle\Tests\Twig\Breakdown\RenderedTaxSummaryRowTest
 */
final readonly class RenderedTaxSummaryRow
{
    private function __construct(
        public string $name,
        public string $rate,
        public TaxCategory $category,
        public TaxType $type,
        public bool $compound,
        public BigDecimal $amount,
        public BigDecimal $taxableBase,
        public BigDecimal $exactTaxableBase,
        public int $sequence,
        public TaxDirection $direction,
        public ?string $note,
    ) {
    }

    public static function fromSummaryRow(TaxSummaryRow $row, CurrencyScale $scale): self
    {
        $printedAmount = $scale->toWholeMinorUnits($row->amount);
        $printedBase = $scale->toWholeMinorUnits($row->taxableBase->plus($row->amount)->minus($printedAmount));

        return new self(
            name: $row->name,
            rate: $row->rate,
            category: $row->category,
            type: $row->type,
            compound: $row->compound,
            amount: $printedAmount,
            taxableBase: $printedBase,
            exactTaxableBase: $row->taxableBase,
            sequence: $row->sequence,
            direction: $row->direction,
            note: $row->note,
        );
    }
}
