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

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class InvoicePaidListener implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.entered.paid' => 'onInvoicePaid',
        ];
    }

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @throws MathException
     */
    public function onInvoicePaid(Event $event): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->getSubject();

        $em = $this->registry->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $em->getRepository(Payment::class);

        $em->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->isGreaterThan($invoice->getTotal())) {
            $client = $invoice->getClient();

            /** @var CreditRepository $creditRepository */
            $creditRepository = $em->getRepository(Credit::class);
            $creditRepository->addCredit($client, $totalPaid->minus($invoice->getTotal()));
        }

        $em->flush();
    }
}
