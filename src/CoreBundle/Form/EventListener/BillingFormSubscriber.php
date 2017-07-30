<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Form\EventListener;

use CSBill\CoreBundle\Entity\Discount;
use CSBill\CoreBundle\Entity\ItemInterface;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\TaxBundle\Entity\Tax;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BillingFormSubscriber implements EventSubscriberInterface
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
     */
    public function __construct(Currency $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Quote|Invoice $object */
        $object = $event->getForm()->getData();
        $total = new Money(0, $this->currency);
        $tax = new Money(0, $this->currency);
        $subTotal = new Money(0, $this->currency);

        $object->getItems()->forAll(function ($_, ItemInterface $item) use (&$total, &$tax, &$subTotal) {
            if (null === $item->getQty()) {
                return false;
            }

            $rowTotal = $item->getPrice()->multiply($item->getQty());

            $total = $total->add($rowTotal);
            $subTotal = $subTotal->add($rowTotal);

            if (null !== $rowTax = $item->getTax()) {
                if (Tax::TYPE_INCLUSIVE === $rowTax->getType()) {
                    $taxAmount = $rowTotal->divide(($rowTax->getRate() / 100) + 1)->subtract($rowTotal)->multiply(-1);
                    $subTotal = $subTotal->subtract($taxAmount);
                } else {
                    $taxAmount = $rowTotal->multiply($rowTax->getRate() / 100);
                    $total = $total->add($taxAmount);
                }

                $tax = $tax->add($taxAmount);
            }

            return true;
        });

        $total = $this->setDiscount($object, $total);

        $object->setBaseTotal($subTotal);
        $object->setTax($tax);
        $object->setTotal($total);
    }

    /**
     * @param Quote|Invoice $object
     * @param Money         $total
     *
     * @return mixed
     */
    private function setDiscount($object, Money $total)
    {
        if (null !== $object->getDiscount()) {
            $discount = $object->getDiscount();

            $discountValue = null;
            if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
                $discountValue = $total->multiply(((float) $discount->getValuePercentage()) / 100);
            } else {
                $discountValue = $discount->getValueMoney()->getMoney();
            }

            $total = $total->subtract($discountValue);

            return $total;
        }

        return $total;
    }
}
