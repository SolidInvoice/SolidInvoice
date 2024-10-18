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
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
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
    public function calculateTotals($entity): void
    {
        if (! $entity instanceof BaseInvoice && ! $entity instanceof Quote) {
            throw new UnexpectedTypeException($entity, 'Invoice or Quote');
        }

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
    private function updateTotal($entity): void
    {
        /** @var BaseInvoice|Quote $entity */

        $total = BigDecimal::zero();
        $subTotal = BigDecimal::zero();
        $tax = BigDecimal::zero();

        foreach ($entity->getLines() as $line) {
            $line->updateTotal();

            $rowTotal = $line->getTotal();

            $total = $total->plus($line->getTotal());
            $subTotal = $subTotal->plus($line->getTotal());

            if (($rowTax = $line->getTax()) instanceof Tax) {
                if (Tax::TYPE_INCLUSIVE === $rowTax->getType()) {
                    $taxAmount = $rowTotal->toBigDecimal()->dividedBy(($rowTax->getRate() / 100) + 1, 2, RoundingMode::HALF_EVEN)->minus($rowTotal)->negated();
                    $subTotal = $subTotal->minus($taxAmount);
                } else {
                    $taxAmount = $rowTotal->toBigDecimal()->multipliedBy($rowTax->getRate() / 100);
                    $total = $total->plus($taxAmount);
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
