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

namespace CSBill\InvoiceBundle\Listener\Doctrine;

use CSBill\CoreBundle\Entity\Discount;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use CSBill\TaxBundle\Entity\Tax;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Money\Money;
use Symfony\Component\Workflow\StateMachine;

class InvoiceSaveListener implements EventSubscriber
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(StateMachine $stateMachine, PaymentRepository $paymentRepository)
    {
        $this->stateMachine = $stateMachine;
        $this->paymentRepository = $paymentRepository;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist => 'prePersist',
        ];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof Invoice) {
            if (!$entity->getStatus()) {
                $this->stateMachine->apply($entity, Graph::TRANSITION_NEW);
            }

            $this->calculateTotal($entity);

            $entity->setBalance($entity->getTotal());

            if ($entity->getId()) {
                $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($entity);
                $entity->setBalance($entity->getTotal()->subtract(new Money($totalPaid, $entity->getTotal()->getCurrency())));
            }
        }
    }

    private function calculateTotal(Invoice $invoice)
    {
        $total = new Money(0, $invoice->getTotal()->getCurrency());
        $subTotal = new Money(0, $invoice->getTotal()->getCurrency());
        $tax = new Money(0, $invoice->getTotal()->getCurrency());

        foreach ($invoice->getItems() as $item) {
            $item->setTotal($item->getPrice()->multiply($item->getQty()));

            $total = $total->add($item->getTotal());
            $subTotal = $subTotal->add($item->getTotal());

            if (!$item->getTax()) {
                continue;
            }

            $taxAmount = $item->getTotal()->multiply($item->getTax()->getRate());

            $tax = $tax->add($taxAmount);

            if (Tax::TYPE_INCLUSIVE === $item->getTax()->getType()) {
                $subTotal = $subTotal->subtract($taxAmount);
            } else {
                $total = $total->add($taxAmount);
            }
        }

        $invoice->setBaseTotal($subTotal);

        if (null !== $invoice->getDiscount()) {
            $discount = $invoice->getDiscount();

            $discountValue = null;
            if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
                $discountValue = $total->multiply(((float) $discount->getValuePercentage()) / 100);
            } else {
                $discountValue = $discount->getValueMoney()->getMoney();
            }

            $total = $total->subtract($discountValue);
        }

        $invoice->setTotal($total);
        $invoice->setTax($tax);
    }
}
