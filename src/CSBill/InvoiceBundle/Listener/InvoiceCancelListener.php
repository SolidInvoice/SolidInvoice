<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\InvoiceBundle\Listener;

use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\PaymentBundle\Model\Status;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ObjectManager;

class InvoiceCancelListener
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoiceCancelled(InvoiceEvent $event)
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository('CSBillPaymentBundle:Payment');

        $invoice->setBalance($invoice->getTotal());
        $this->manager->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid > 0) {
            $paymentRepository->updatePaymentStatus($invoice->getPayments(), Status::STATUS_CREDIT);

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->manager->getRepository('CSBillClientBundle:Credit');

            $creditRepository->addCredit($invoice->getClient(), $totalPaid);
        }

        $this->manager->flush();
    }
}
