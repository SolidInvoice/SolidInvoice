<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener;

use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\MoneyBundle\Currency;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Money\Money;

class InvoicePaidListener
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param ObjectManager $manager
     * @param Currency      $currency
     */
    public function __construct(ObjectManager $manager, Currency $currency)
    {
        $this->manager = $manager;
        $this->currency = $currency->getCurrency();
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoicePaid(InvoiceEvent $event)
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository('CSBillPaymentBundle:Payment');

        $invoice->setBalance(new Money(0, $this->currency));
        $this->manager->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->greaterThan($invoice->getTotal())) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->manager->getRepository('CSBillClientBundle:Credit');
            $creditRepository->addCredit($client, $totalPaid->subtract($invoice->getTotal()));
        }

        $this->manager->flush();
    }
}
