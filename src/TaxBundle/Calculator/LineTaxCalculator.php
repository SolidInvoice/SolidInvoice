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

namespace SolidInvoice\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\TaxBundle\Calculator\Result\LineBreakdown;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * Computes the per-line tax breakdown for an invoice or quote line.
 *
 * Dispatches on each {@see LineTax::$typeSnapshot} value:
 *
 * - {@see TaxType::Inclusive} — the rate is *extracted* from the gross line total,
 *   shrinking the line subtotal. The gross line total stays unchanged.
 *   Compound + Inclusive is rejected by
 *   {@see \SolidInvoice\TaxBundle\Validator\Constraints\IncompatibleTaxConfiguration}.
 * - {@see TaxType::Exclusive} — the rate is applied as a percentage on top of the base.
 *   Base is `subtotal` for non-compound or `subtotal + accumulated non-compound tax`
 *   when compound, allowing layered jurisdictions (e.g. Quebec QST on top of GST) to
 *   stack correctly.
 * - {@see TaxType::FlatRate} — a fixed currency amount, `rate_snapshot × 100` to convert
 *   major units to minor units. Compound + FlatRate is rejected by the same constraint.
 *
 * Category branches:
 * - {@see TaxCategory::Exempt} rows are skipped entirely.
 * - {@see TaxCategory::ZeroRated}, {@see TaxCategory::OutOfScope},
 *   {@see TaxCategory::ReverseCharge} produce a summary row with amount = 0 (so the
 *   row still appears on the invoice/quote for compliance/clarity).
 *
 * @see \SolidInvoice\TaxBundle\Tests\Calculator\LineTaxCalculatorTest
 */
final class LineTaxCalculator
{
    /**
     * @throws MathException
     */
    public function calculateLine(LineInterface $line, Rounder $rounder): LineBreakdown
    {
        $gross = BigNumber::of($line->getTotal())->toBigDecimal();
        $subtotal = $gross;
        $lineTotal = $gross;
        $totalTax = BigDecimal::zero();
        $accumulatedNonCompound = BigDecimal::zero();
        $taxRows = [];

        foreach ($this->orderedTaxes($line) as $lineTax) {
            $category = $lineTax->getCategorySnapshot();

            if ($category === TaxCategory::Exempt) {
                continue;
            }

            if ($category !== TaxCategory::Standard) {
                $taxRows[] = $this->summary($lineTax, BigDecimal::zero());
                continue;
            }

            $type = $lineTax->getTypeSnapshot();
            $rate = BigDecimal::of($lineTax->getRateSnapshot());

            switch ($type) {
                case TaxType::Inclusive:
                    $amount = $this->extractInclusive($gross, $rate, $rounder);
                    $subtotal = $subtotal->minus($amount);
                    break;

                case TaxType::Exclusive:
                    $base = $lineTax->isCompound() ? $subtotal->plus($accumulatedNonCompound) : $subtotal;
                    $amount = $this->applyExclusive($base, $rate, $rounder);
                    $lineTotal = $lineTotal->plus($amount);
                    if (! $lineTax->isCompound()) {
                        $accumulatedNonCompound = $accumulatedNonCompound->plus($amount);
                    }

                    break;

                case TaxType::FlatRate:
                    $amount = $this->applyFlatRate($rate, $rounder);
                    $lineTotal = $lineTotal->plus($amount);
                    if (! $lineTax->isCompound()) {
                        $accumulatedNonCompound = $accumulatedNonCompound->plus($amount);
                    }

                    break;
            }

            $totalTax = $totalTax->plus($amount);
            $taxRows[] = $this->summary($lineTax, $amount);
        }

        return new LineBreakdown($subtotal, $lineTotal, $totalTax, $taxRows);
    }

    /**
     * Extract the inclusive tax component from a gross figure.
     *
     * @throws MathException
     */
    private function extractInclusive(BigDecimal $gross, BigDecimal $rate, Rounder $rounder): BigDecimal
    {
        $divisor = $rate->dividedBy(100, 10, RoundingMode::HalfEven)->plus(1);
        $net = $gross->dividedBy($divisor, 2, $rounder->getStrategy()->toRoundingMode());

        return $gross->minus($net);
    }

    /**
     * @throws MathException
     */
    private function applyExclusive(BigDecimal $base, BigDecimal $rate, Rounder $rounder): BigDecimal
    {
        return $rounder->round($base->multipliedBy($rate->dividedBy(100, 10, RoundingMode::HalfEven)));
    }

    /**
     * @throws MathException
     */
    private function applyFlatRate(BigDecimal $rate, Rounder $rounder): BigDecimal
    {
        return $rounder->round($rate->multipliedBy(100));
    }

    private function summary(LineTax $lineTax, BigDecimal $amount): TaxSummaryRow
    {
        return new TaxSummaryRow(
            name: (string) $lineTax->getNameSnapshot(),
            rate: $lineTax->getRateSnapshot(),
            category: $lineTax->getCategorySnapshot(),
            type: $lineTax->getTypeSnapshot(),
            compound: $lineTax->isCompound(),
            amount: $amount,
            sequence: $lineTax->getSequence(),
        );
    }

    /**
     * @return list<LineTax>
     */
    private function orderedTaxes(LineInterface $line): array
    {
        $taxes = [];

        foreach ($line->getTaxes() as $lineTax) {
            if ($lineTax instanceof LineTax) {
                $taxes[] = $lineTax;
            }
        }

        usort(
            $taxes,
            static fn (LineTax $a, LineTax $b): int => $a->getSequence() <=> $b->getSequence()
        );

        return $taxes;
    }
}
