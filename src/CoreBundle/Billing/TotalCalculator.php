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
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;

/**
 * Populates {@see BaseInvoice}/{@see Quote} totals (subtotal, tax, grand total,
 * balance) by delegating tax math to {@see TaxCalculatorInterface} and discount math
 * to {@see Calculator::calculateDiscount()}.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Billing\TotalCalculatorTest
 */
class TotalCalculator
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly Calculator $calculator,
        private readonly TaxCalculatorInterface $taxCalculator,
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
        $result = $this->taxCalculator->calculate($entity);

        $subTotal = $result->subTotal;
        $tax = $result->getTotalTax();
        $total = $result->total;
        $withholding = $result->totalWithholding;

        $entity->setBaseTotal($subTotal);

        if ($entity->getDiscount()->getValue()) {
            $total = $this->applyDiscount($entity, $total);
        }

        $entity->setTotal($total);
        $entity->setTax($tax);
        $entity->setWithholdingAmount($withholding);
        $entity->setPayableAmount(BigDecimal::of($total)->minus($withholding));
    }

    /**
     * @throws MathException
     */
    private function applyDiscount(BaseInvoice|Quote $entity, BigDecimal|BigInteger $total): BigNumber
    {
        return $total->minus($this->calculator->calculateDiscount($entity));
    }
}
