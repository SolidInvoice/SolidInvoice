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
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
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
     * @throws MathException
     */
    public function calculateTotals(BaseInvoice|Quote $entity): void
    {
        $this->updateTotal($entity);

        if ($entity instanceof Invoice) {
            $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($entity);
            $total = $entity->getTotal();
            assert($total instanceof BigDecimal || $total instanceof BigInteger);

            $entity->setBalance($total->minus($totalPaid));
        }
    }

    /**
     * @throws MathException
     */
    private function updateTotal(BaseInvoice|Quote $entity): void
    {

        $total = BigDecimal::zero();
        $subTotal = BigDecimal::zero();
        $tax = BigDecimal::zero();

        foreach ($entity->getLines() as $line) {
            $line->updateTotal();

            $rowTotal = $line->getTotal();

            $total = $total->plus($line->getTotal());
            $subTotal = $subTotal->plus($line->getTotal());

            if (($rowTax = $line->getTax()) instanceof Tax) {
                switch ($rowTax->getType()) {
                    case Tax::TYPE_INCLUSIVE:
                        $taxAmount = $rowTotal->toBigDecimal()->dividedBy(($rowTax->getRate() / 100) + 1, 2, RoundingMode::HalfEven)->minus($rowTotal)->negated();
                        $subTotal = $subTotal->minus($taxAmount);
                        break;
                    case Tax::TYPE_EXCLUSIVE:
                        $taxAmount = $rowTotal->toBigDecimal()->multipliedBy($rowTax->getRate() / 100)->toScale(0, RoundingMode::HalfEven);
                        $total = $total->plus($taxAmount);
                        break;
                    case Tax::TYPE_FLAT_RATE:
                        $taxAmount = BigDecimal::of($rowTax->getRate())->multipliedBy(100)->toScale(0, RoundingMode::HalfEven);
                        $total = $total->plus($taxAmount);
                        break;
                    default:
                        $taxAmount = BigDecimal::zero();
                        break;
                }

                $tax = $tax->plus($taxAmount);
            }
        }

        $entity->setBaseTotal($subTotal);

        if ($entity->getDiscount()->getValue()) {
            $total = $this->setDiscount($entity, $total);
        }

        $entity->setTotal($total);
        $entity->setTax($tax);
    }

    /**
     * @throws MathException
     */
    private function setDiscount(BaseInvoice|Quote $entity, BigDecimal|BigInteger $total): BigNumber
    {
        return $total->minus($this->calculator->calculateDiscount($entity));
    }
}
