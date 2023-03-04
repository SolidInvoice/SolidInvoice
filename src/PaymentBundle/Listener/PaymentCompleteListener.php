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

use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
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
use Symfony\Component\Workflow\StateMachine;

class PaymentCompleteListener implements EventSubscriberInterface
{
    private RouterInterface $router;

    private ManagerRegistry $registry;

    private StateMachine $stateMachine;

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
        StateMachine $stateMachine,
        ManagerRegistry $registry,
        RouterInterface $router
    ) {
        $this->router = $router;
        $this->registry = $registry;
        $this->stateMachine = $stateMachine;
    }

    public function onPaymentComplete(PaymentCompleteEvent $event): void
    {
        $payment = $event->getPayment();
        $status = (string) $payment->getStatus();

        if ('credit' === $payment->getMethod()->getGatewayName()) {
            $creditRepository = $this->registry->getRepository(Credit::class);
            $creditRepository->deductCredit(
                $payment->getClient(),
                new Money($payment->getTotalAmount(), new Currency($payment->getCurrencyCode()))
            );
        }

        if (null !== $invoice = $event->getPayment()->getInvoice()) {
            $em = $this->registry->getManager();

            if (Status::STATUS_CAPTURED === $status && $em->getRepository(Invoice::class)->isFullyPaid($invoice)) {
                $this->stateMachine->apply($invoice, Graph::TRANSITION_PAY);
            } else {
                $paymentRepository = $this->registry->getRepository(Payment::class);
                $invoiceTotal = $invoice->getTotal();
                $totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $invoiceTotal->getCurrency());
                $invoice->setBalance($invoiceTotal->subtract($totalPaid));

                $em = $this->registry->getManager();
                $em->persist($invoice);
                $em->flush();
            }

            $router = $this->router;

            $event->setResponse(
                new class($router->generate('_view_invoice_external', ['uuid' => $invoice->getUuid()]), $status) extends RedirectResponse implements FlashResponse {
                    /**
                     * @var string
                     */
                    private $status;

                    public function __construct(string $route, string $status)
                    {
                        parent::__construct($route);

                        $this->status = $status;
                    }

                    public function getFlash(): \Generator
                    {
                        yield from PaymentCompleteListener::addFlashMessage($this->status);
                    }
                }
            );
        }
    }

    public static function addFlashMessage(string $status): \Generator
    {
        switch ($status) {
            case Status::STATUS_CAPTURED:
                yield FlashResponse::FLASH_SUCCESS => 'payment.flash.status.success';

                break;

            case Status::STATUS_CANCELLED:
                yield FlashResponse::FLASH_DANGER => 'payment.flash.status.cancelled';

                break;

            case Status::STATUS_PENDING:
                yield FlashResponse::FLASH_WARNING => 'payment.flash.status.pending';

                break;

            case Status::STATUS_EXPIRED:
                yield FlashResponse::FLASH_DANGER => 'payment.flash.status.expired';

                break;

            case Status::STATUS_FAILED:
                yield FlashResponse::FLASH_DANGER => 'payment.flash.status.failed';

                break;

            case Status::STATUS_NEW:
                yield FlashResponse::FLASH_WARNING => 'payment.flash.status.new';

                break;

            case Status::STATUS_SUSPENDED:
                yield FlashResponse::FLASH_DANGER => 'payment.flash.status.suspended';

                break;

            case Status::STATUS_AUTHORIZED:
                yield FlashResponse::FLASH_INFO => 'payment.flash.status.authorized';

                break;

            case Status::STATUS_REFUNDED:
                yield FlashResponse::FLASH_WARNING => 'payment.flash.status.refunded';

                break;

            case Status::STATUS_UNKNOWN:
            default:
                yield FlashResponse::FLASH_DANGER => 'payment.flash.status.unknown';

                break;
        }
    }
}
