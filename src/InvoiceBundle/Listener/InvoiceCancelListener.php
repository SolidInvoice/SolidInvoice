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
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function assert;

class InvoiceCancelListener implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceEvents::INVOICE_POST_CANCEL => 'onInvoiceCancelled',
        ];
    }

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @throws MathException
     */
    public function onInvoiceCancelled(InvoiceEvent $event): void
    {
        $invoice = $event->getInvoice();

        assert($invoice instanceof Invoice);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->registry->getRepository(Payment::class);

        $em = $this->registry->getManager();

        $invoice->setBalance($invoice->getTotal());
        $em->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->isPositive()) {
            $paymentRepository->updatePaymentStatus($invoice->getPayments(), Status::STATUS_CREDIT);

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->registry->getRepository(Credit::class);

            $creditRepository->addCredit($invoice->getClient(), $totalPaid);
        }

        $em->flush();
    }
}
