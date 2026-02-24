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
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
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
        $status = $payment->getStatus()?->value ?? '';

        if ('credit' === $payment->getMethod()?->getGatewayName()) {
            $creditRepository = $this->registry->getRepository(Credit::class);
            $creditRepository->deductCredit(
                $payment->getClient(),
                $payment->getTotalAmount(),
            );
        }

        if (($invoice = $event->getPayment()->getInvoice()) instanceof Invoice) {
            $em = $this->registry->getManager();

            if (PaymentStatus::Captured->value === $status && $em->getRepository(Invoice::class)->isFullyPaid($invoice)) {
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
            PaymentStatus::Captured->value => yield FlashResponse::FLASH_SUCCESS => 'payment.flash.status.success',
            PaymentStatus::Cancelled->value => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.cancelled',
            PaymentStatus::Pending->value => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.pending',
            PaymentStatus::Expired->value => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.expired',
            PaymentStatus::Failed->value => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.failed',
            PaymentStatus::New->value => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.new',
            PaymentStatus::Suspended->value => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.suspended',
            PaymentStatus::Authorized->value => yield FlashResponse::FLASH_INFO => 'payment.flash.status.authorized',
            PaymentStatus::Refunded->value => yield FlashResponse::FLASH_WARNING => 'payment.flash.status.refunded',
            default => yield FlashResponse::FLASH_DANGER => 'payment.flash.status.unknown',
        };
    }
}
