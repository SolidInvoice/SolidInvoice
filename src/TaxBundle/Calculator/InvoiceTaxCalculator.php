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
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\Result\InvoiceLevelBreakdown;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * Computes whole-document (invoice-level) tax breakdowns.
 *
 * Each {@see InvoiceTax} dispatches on its {@see TaxDirection}:
 *
 * - {@see TaxDirection::Additive} — adds to {@see InvoiceLevelBreakdown::$totalInvoiceLevelTax};
 *   used for invoice-wide surcharges, shipping, environmental fees, etc.
 * - {@see TaxDirection::Deductive} — adds to {@see InvoiceLevelBreakdown::$totalWithholding};
 *   used for TDS / withholding taxes that reduce what the client pays without
 *   reducing the invoice grand total.
 * - {@see TaxDirection::Informational} — emits a row with `amount = 0` so the
 *   note can still be rendered (reverse-charge VAT in particular).
 *
 * For percentage rates, the base is the document subtotal (line subtotals,
 * tax-exclusive). Flat-rate amounts are independent of base. Inclusive rates
 * extract from `subTotal + totalLineTax`.
 * @see \SolidInvoice\TaxBundle\Tests\Calculator\InvoiceTaxCalculatorTest
 */
final class InvoiceTaxCalculator
{
    /**
     * @throws MathException
     */
    public function calculateInvoiceLevel(
        BaseInvoice|Quote $document,
        BigDecimal $subTotal,
        BigDecimal $totalLineTax,
        Rounder $rounder,
    ): InvoiceLevelBreakdown {
        $invoiceTaxes = $this->orderedTaxes($document);

        if ($invoiceTaxes === []) {
            return InvoiceLevelBreakdown::empty();
        }

        $totalAdditive = BigDecimal::zero();
        $totalWithholding = BigDecimal::zero();
        $rows = [];

        foreach ($invoiceTaxes as $invoiceTax) {
            $direction = $invoiceTax->getDirection();

            if ($direction === TaxDirection::Informational) {
                $rows[] = $this->summary($invoiceTax, BigDecimal::zero());
                continue;
            }

            $amount = $this->computeAmount($invoiceTax, $subTotal, $totalLineTax, $rounder);
            $rows[] = $this->summary($invoiceTax, $amount);

            if ($direction === TaxDirection::Deductive) {
                $totalWithholding = $totalWithholding->plus($amount);
                continue;
            }

            $totalAdditive = $totalAdditive->plus($amount);
        }

        return new InvoiceLevelBreakdown($totalAdditive, $rows, $totalWithholding);
    }

    /**
     * @throws MathException
     */
    private function computeAmount(
        InvoiceTax $invoiceTax,
        BigDecimal $subTotal,
        BigDecimal $totalLineTax,
        Rounder $rounder,
    ): BigDecimal {
        $rate = BigDecimal::of($invoiceTax->getRateSnapshot());

        return match ($this->typeFor($invoiceTax)) {
            TaxType::Inclusive => $this->extractInclusive($subTotal->plus($totalLineTax), $rate, $rounder),
            TaxType::Exclusive => $rounder->round($subTotal->multipliedBy($rate->dividedBy(100, 10, RoundingMode::HalfEven))),
            TaxType::FlatRate => $rounder->round($rate->multipliedBy(100)),
        };
    }

    /**
     * @throws MathException
     */
    private function extractInclusive(BigDecimal $gross, BigDecimal $rate, Rounder $rounder): BigDecimal
    {
        if ($rate->isZero()) {
            return BigDecimal::zero();
        }

        $divisor = $rate->dividedBy(100, 10, RoundingMode::HalfEven)->plus(1);
        $net = $gross->dividedBy($divisor, 2, $rounder->getStrategy()->toRoundingMode());

        return $gross->minus($net);
    }

    private function typeFor(InvoiceTax $invoiceTax): TaxType
    {
        return $invoiceTax->getTypeSnapshot();
    }

    private function summary(InvoiceTax $invoiceTax, BigDecimal $amount): TaxSummaryRow
    {
        return new TaxSummaryRow(
            name: (string) $invoiceTax->getNameSnapshot(),
            rate: $invoiceTax->getRateSnapshot(),
            category: $invoiceTax->getCategorySnapshot(),
            type: $this->typeFor($invoiceTax),
            compound: false,
            amount: $amount,
            sequence: $invoiceTax->getSequence(),
            direction: $invoiceTax->getDirection(),
            note: $invoiceTax->getNote(),
        );
    }

    /**
     * @return list<InvoiceTax>
     */
    private function orderedTaxes(BaseInvoice|Quote $document): array
    {
        $collection = match (true) {
            $document instanceof Invoice,
            $document instanceof RecurringInvoice,
            $document instanceof Quote => $document->getInvoiceTaxes(),
            default => null,
        };

        if ($collection->isEmpty()) {
            return [];
        }

        $taxes = [];

        foreach ($collection as $tax) {
            if ($tax instanceof InvoiceTax) {
                $taxes[] = $tax;
            }
        }

        usort(
            $taxes,
            static fn (InvoiceTax $a, InvoiceTax $b): int => $a->getSequence() <=> $b->getSequence()
        );

        return $taxes;
    }
}
