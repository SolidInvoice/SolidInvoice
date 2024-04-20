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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Billing\TotalCalculatorTest
 */
class TotalCalculator
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository
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
            $entity->setBalance($entity->getTotal()->minus($totalPaid));
        }
    }

    /**
     * @throws MathException
     */
    private function updateTotal($entity): void
    {
        /** @var BaseInvoice|Quote $entity */

        $total = BigInteger::zero();
        $subTotal = BigInteger::zero();
        $tax = BigInteger::zero();

        foreach ($entity->getItems() as $item) {
            $item->setTotal($item->getPrice()->multipliedBy($item->getQty()));

            $rowTotal = $item->getTotal();

            $total = $total->plus($item->getTotal());
            $subTotal = $subTotal->plus($item->getTotal());

            if (($rowTax = $item->getTax()) instanceof Tax) {
                if (Tax::TYPE_INCLUSIVE === $rowTax->getType()) {
                    $taxAmount = $rowTotal->toBigDecimal()->dividedBy(($rowTax->getRate() / 100) + 1)->minus($rowTotal)->negated();
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
    private function setDiscount(BaseInvoice|Quote $entity, BigInteger $total): BigInteger
    {
        $discount = $entity->getDiscount();

        if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
            $discountValue = $total->toBigDecimal()->multipliedBy(((float) $discount->getValuePercentage()) / 100);
        } else {
            $discountValue = $discount->getValueMoney();
        }

        return $total->minus($discountValue);
    }
}
