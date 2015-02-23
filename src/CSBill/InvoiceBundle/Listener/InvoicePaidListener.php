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
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ObjectManager;

class InvoicePaidListener
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
    public function onInvoicePaid(InvoiceEvent $event)
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository('CSBillPaymentBundle:Payment');

        $invoice->setBalance(0);
        $this->manager->persist($invoice);

        if (($totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice)) > $invoice->getTotal()) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->manager->getRepository('CSBillClientBundle:Credit');
            $creditRepository->addCredit($client, ($totalPaid - $invoice->getTotal()));
        }

        $this->manager->flush();
    }
}