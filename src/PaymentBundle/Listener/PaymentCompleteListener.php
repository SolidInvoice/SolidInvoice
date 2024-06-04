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

namespace SolidInvoice\PaymentBundle\Listener;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Model\Status;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PaymentCompleteListener implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_COMPLETE => 'onPaymentComplete',
        ];
    }

    public function __construct(
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly ManagerRegistry $registry,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @throws MathException
     */
    public function onPaymentComplete(PaymentCompleteEvent $event): void
    {
        $payment = $event->getPayment();
        $status = (string) $payment->getStatus();

        if ('credit' === $payment->getMethod()?->getGatewayName()) {
            $creditRepository = $this->registry->getRepository(Credit::class);
            $creditRepository->deductCredit(
                $payment->getClient(),
                $payment->getTotalAmount(),
            );
        }

        if (($invoice = $event->getPayment()->getInvoice()) instanceof Invoice) {
            $em = $this->registry->getManager();

            if (Status::STATUS_CAPTURED === $status && $em->getRepository(Invoice::class)->isFullyPaid($invoice)) {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_PAY);
            } else {
                $paymentRepository = $this->registry->getRepository(Payment::class);
                $invoiceTotal = $invoice->getTotal();
                $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);
                $invoice->setBalance($invoiceTotal->toBigDecimal()->minus($totalPaid));

                $em = $this->registry->getManager();
                $em->persist($invoice);
                $em->flush();
            }

            $router = $this->router;

            $event->setResponse(
                new class($router->generate('_view_invoice_external', ['uuid' => $invoice->getUuid()]), $status) extends RedirectResponse implements FlashResponse {
                    public function __construct(
                        string $route,
                        private readonly string $paymentStatus
                    ) {
                        parent::__construct($route);
                    }

                    public function getFlash(): Generator
                    {
                        yield from PaymentCompleteListener::addFlashMessage($this->paymentStatus);
                    }
                }
            );
        }
    }

    public static function addFlashMessage(string $status): Generator
    {
        match ($status) {
            Status::STATUS_CAPTURED => yield FlashResponse::FLASH_SUCCESS => 'payment.flash.status.success',
            Status::STATUS_CANCELLED => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.cancelled',
            Status::STATUS_PENDING => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.pending',
            Status::STATUS_EXPIRED => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.expired',
            Status::STATUS_FAILED => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.failed',
            Status::STATUS_NEW => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.new',
            Status::STATUS_SUSPENDED => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.suspended',
            Status::STATUS_AUTHORIZED => yield FlashResponse::FLASH_INFO => 'payment.flash.status.authorized',
            Status::STATUS_REFUNDED => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.refunded',
            default => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.unknown',
        };
    }
}
