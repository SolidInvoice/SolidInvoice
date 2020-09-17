<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Billing;

use Money\Money;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Exception\UnexpectedTypeException;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

class TotalCalculator
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function calculateTotals($entity)
    {
        if (!$entity instanceof BaseInvoice && !$entity instanceof Quote) {
            throw new UnexpectedTypeException($entity, 'Invoice or Quote');
        }

        $this->updateTotal($entity);

        if ($entity instanceof Invoice) {
            $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($entity);
            $entity->setBalance($entity->getTotal()->subtract(new Money($totalPaid, $entity->getTotal()->getCurrency())));
        }
    }

    private function updateTotal($entity)
    {
        /* @var BaseInvoice|Quote $entity */

        $total = new Money(0, $entity->getTotal()->getCurrency());
        $subTotal = new Money(0, $entity->getTotal()->getCurrency());
        $tax = new Money(0, $entity->getTotal()->getCurrency());

        foreach ($entity->getItems() as $item) {
            $item->setTotal($item->getPrice()->multiply($item->getQty()));

            $rowTotal = $item->getTotal();

            $total = $total->add($item->getTotal());
            $subTotal = $subTotal->add($item->getTotal());

            if (null !== $rowTax = $item->getTax()) {
                $this->setTax($rowTax, $rowTotal, $subTotal, $total, $tax);
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
     * @param BaseInvoice|Quote $entity
     */
    private function setDiscount($entity, Money $total): Money
    {
        $discount = $entity->getDiscount();

        $discountValue = null;
        if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
            $discountValue = $total->multiply(((float) $discount->getValuePercentage()) / 100);
        } else {
            $discountValue = $discount->getValueMoney()->getMoney();
        }

        return $total->subtract($discountValue);
    }

    private function setTax(Tax $rowTax, Money $rowTotal, Money &$subTotal, Money &$total, Money &$tax): void
    {
        if (Tax::TYPE_INCLUSIVE === $rowTax->getType()) {
            $taxAmount = $rowTotal->divide(($rowTax->getRate() / 100) + 1)->subtract($rowTotal)->multiply(-1);
            $subTotal = $subTotal->subtract($taxAmount);
        } else {
            $taxAmount = $rowTotal->multiply($rowTax->getRate() / 100);
            $total = $total->add($taxAmount);
        }

        $tax = $tax->add($taxAmount);
    }
}
