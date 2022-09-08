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
use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceCancelListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            InvoiceEvents::INVOICE_POST_CANCEL => 'onInvoiceCancelled',
        ];
    }

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(ManagerRegistry $registry, Currency $currency)
    {
        $this->registry = $registry;
        $this->currency = $currency;
    }

    public function onInvoiceCancelled(InvoiceEvent $event): void
    {
        $invoice = $event->getInvoice();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->registry->getRepository(Payment::class);

        $em = $this->registry->getManager();

        $invoice->setBalance($invoice->getTotal());
        $em->persist($invoice);

        $totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $invoice->getClient()->getCurrency() ?: $this->currency);

        if ($totalPaid->isPositive()) {
            $paymentRepository->updatePaymentStatus($invoice->getPayments(), Status::STATUS_CREDIT);

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->registry->getRepository(Credit::class);

            $creditRepository->addCredit($invoice->getClient(), $totalPaid);
        }

        $em->flush();
    }
}
