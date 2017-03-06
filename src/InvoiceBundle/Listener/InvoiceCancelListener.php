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
use Money\Currency;
use Money\Money;

class InvoiceCancelListener
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
    public function onInvoiceCancelled(InvoiceEvent $event)
    {
	$invoice = $event->getInvoice();

	/** @var PaymentRepository $paymentRepository */
	$paymentRepository = $this->registry->getRepository('CSBillPaymentBundle:Payment');

	$em = $this->registry->getManager();

	$invoice->setBalance($invoice->getTotal());
	$em->persist($invoice);

	$totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $invoice->getClient()->getCurrency() ?: $this->currency);

	if ($totalPaid->isPositive()) {
	    $paymentRepository->updatePaymentStatus($invoice->getPayments(), Status::STATUS_CREDIT);

	    /** @var CreditRepository $creditRepository */
	    $creditRepository = $this->registry->getRepository('CSBillClientBundle:Credit');

	    $creditRepository->addCredit($invoice->getClient(), $totalPaid);
	}

	$em->flush();
    }
}
