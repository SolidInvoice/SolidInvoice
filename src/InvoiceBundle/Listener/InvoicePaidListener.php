<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Listener;

use Doctrine\Persistence\ManagerRegistry;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InvoicePaidListener implements EventSubscriberInterface
{
    private ManagerRegistry $registry;

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.entered.paid' => 'onInvoicePaid',
        ];
    }

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onInvoicePaid(Event $event): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->getSubject();

        $em = $this->registry->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $em->getRepository(Payment::class);

        $currency = $invoice->getClient()->getCurrency();

        $invoice->setBalance(new Money(0, $currency));
        $em->persist($invoice);

        $totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $currency);

        if ($totalPaid->greaterThan($invoice->getTotal())) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $em->getRepository(Credit::class);
            $creditRepository->addCredit($client, $totalPaid->subtract($invoice->getTotal()));
        }

        $em->flush();
    }
}
