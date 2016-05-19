<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Form\EventListener;

use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item as InvoiceItem;
use CSBill\QuoteBundle\Entity\Item as QuoteItem;
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

        $object->getItems()->forAll(function ($key, $item) use (&$total, &$tax, &$subTotal) {
            /** @var QuoteItem|InvoiceItem $item */
            if (null === $item->getQty()) {
                return false;
            }

            $rowTotal = $item->getPrice()->multiply($item->getQty());

            $total = $total->add($rowTotal);
            $subTotal = $subTotal->add($rowTotal);

            if (null !== $rowTax = $item->getTax()) {
                $taxAmount = $rowTotal->multiply($rowTax->getRate());

                $tax = $tax->add($taxAmount);

                if (Tax::TYPE_INCLUSIVE === $rowTax->getType()) {
                    $subTotal = $subTotal->subtract($taxAmount);
                } else {
                    $total = $total->add($taxAmount);
                }
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
            $discount = $total->multiply($object->getDiscount());

            $total = $total->subtract($discount);

            return $total;
        }

        return $total;
    }
}
