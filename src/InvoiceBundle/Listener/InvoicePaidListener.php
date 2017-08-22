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

namespace SolidInvoice\InvoiceBundle\Listener;

use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InvoicePaidListener implements EventSubscriberInterface
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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.invoice.entered.paid' => 'onInvoicePaid',
        ];
    }

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
     * @param Event $event
     */
    public function onInvoicePaid(Event $event)
    {
        /** @var Invoice $invoice */
        $invoice = $event->getSubject();

        $em = $this->registry->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $em->getRepository('SolidInvoicePaymentBundle:Payment');

        $currency = $invoice->getClient()->getCurrency() ?? $this->currency;

        $invoice->setBalance(new Money(0, $currency));
        $em->persist($invoice);

        $totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $currency);

        if ($totalPaid->greaterThan($invoice->getTotal())) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $em->getRepository('SolidInvoiceClientBundle:Credit');
            $creditRepository->addCredit($client, $totalPaid->subtract($invoice->getTotal()));
        }

        $em->flush();
    }
}
