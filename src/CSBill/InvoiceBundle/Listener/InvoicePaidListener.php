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
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;

class InvoicePaidListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param ManagerRegistry $registry
     * @param Currency        $currency
     */
    public function __construct(ManagerRegistry $registry, Currency $currency)
    {
        $this->registry = $registry;
        $this->currency = $currency;
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoicePaid(InvoiceEvent $event)
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->registry->getRepository('CSBillPaymentBundle:Payment');

        $em = $this->registry->getManager();

        $invoice->setBalance(new Money(0, $this->currency));
        $em->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->greaterThan($invoice->getTotal())) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->registry->getRepository('CSBillClientBundle:Credit');
            $creditRepository->addCredit($client, $totalPaid->subtract($invoice->getTotal()));
        }

        $em->flush();
    }
}
