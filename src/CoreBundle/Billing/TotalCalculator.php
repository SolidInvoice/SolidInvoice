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

namespace SolidInvoice\CoreBundle\Billing;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Billing\TotalCalculatorTest
 */
class TotalCalculator
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly Calculator $calculator,
    ) {
    }

    /**
     * @param iterable<LineInterface> $lines
     * @throws MathException
     */
    public function calculateFromLines(iterable $lines, Discount $discount): TotalsResult
    {
        $total = BigDecimal::zero();
        $subTotal = BigDecimal::zero();
        $tax = BigDecimal::zero();

        foreach ($lines as $line) {
            $line->updateTotal();

            $rowTotal = $line->getTotal();
            $total = $total->plus($rowTotal);
            $subTotal = $subTotal->plus($rowTotal);

            if (($rowTax = $line->getTax()) instanceof Tax) {
                $taxAmount = match ($rowTax->getType()) {
                    Tax::TYPE_INCLUSIVE => $rowTotal->toBigDecimal()
                        ->dividedBy(($rowTax->getRate() / 100) + 1, 2, RoundingMode::HALF_EVEN)
                        ->minus($rowTotal)
                        ->negated(),
                    Tax::TYPE_EXCLUSIVE => $rowTotal->toBigDecimal()
                        ->multipliedBy($rowTax->getRate() / 100)
                        ->toScale(0, RoundingMode::HALF_EVEN),
                    Tax::TYPE_FLAT_RATE => BigDecimal::of($rowTax->getRate())
                        ->multipliedBy(100)
                        ->toScale(0, RoundingMode::HALF_EVEN),
                    default => BigDecimal::zero(),
                };

                if ($rowTax->getType() === Tax::TYPE_INCLUSIVE) {
                    $subTotal = $subTotal->minus($taxAmount);
                } else {
                    $total = $total->plus($taxAmount);
                }

                $tax = $tax->plus($taxAmount);
            }
        }

        if ($discount->getValue()) {
            $discountAmount = $this->calculator->calculateDiscountFromValues($subTotal, $tax, $discount);
            $total = $total->minus($discountAmount);
        }

        return new TotalsResult($total, $subTotal, $tax);
    }

    /**
     * @throws MathException
     */
    public function calculateTotals(BaseInvoice|Quote $entity): void
    {
        $result = $this->calculateFromLines($entity->getLines(), $entity->getDiscount());

        $entity->setBaseTotal($result->baseTotal);
        $entity->setTotal($result->total);
        $entity->setTax($result->tax);

        if ($entity instanceof Invoice) {
            $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($entity);
            $entity->setBalance($result->total->minus($totalPaid));
        }
    }
}
