<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener;

use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\PaymentBundle\Model\Status;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class InvoiceCancelListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoiceCancelled(InvoiceEvent $event)
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->registry->getRepository('CSBillPaymentBundle:Payment');

        $em = $this->registry->getManager();

        $invoice->setBalance($invoice->getTotal());
        $em->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->isPositive()) {
            $paymentRepository->updatePaymentStatus($invoice->getPayments(), Status::STATUS_CREDIT);

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->registry->getRepository('CSBillClientBundle:Credit');

            $creditRepository->addCredit($invoice->getClient(), $totalPaid);
        }

        $em->flush();
    }
}
