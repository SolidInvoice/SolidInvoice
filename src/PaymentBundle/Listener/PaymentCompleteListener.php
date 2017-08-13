<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Listener;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use CSBill\PaymentBundle\Model\Status;
use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\StateMachine;

class PaymentCompleteListener implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PaymentEvents::PAYMENT_COMPLETE => 'onPaymentComplete',
        ];
    }

    /**
     * @param StateMachine    $stateMachine
     * @param ManagerRegistry $registry
     * @param RouterInterface $router
     * @param Currency        $currency
     */
    public function __construct(
        StateMachine $stateMachine,
        ManagerRegistry $registry,
        RouterInterface $router,
        Currency $currency
    ) {
        $this->router = $router;
        $this->registry = $registry;
        $this->currency = $currency;
        $this->stateMachine = $stateMachine;
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentComplete(PaymentCompleteEvent $event)
    {
        $payment = $event->getPayment();
        $status = (string) $payment->getStatus();

        if ('credit' === $payment->getMethod()->getGatewayName()) {
            $creditRepository = $this->registry->getRepository('CSBillClientBundle:Credit');
            $creditRepository->deductCredit(
                $payment->getClient(),
                new Money($payment->getTotalAmount(), $this->currency)
            );
        }

        if (null !== $invoice = $event->getPayment()->getInvoice()) {
            $em = $this->registry->getManager();

            if ($status === Status::STATUS_CAPTURED && $em->getRepository('CSBillInvoiceBundle:Invoice')->isFullyPaid($invoice)) {
                $this->stateMachine->apply($invoice, Graph::TRANSITION_PAY);
            } else {
                $paymentRepository = $this->registry->getRepository('CSBillPaymentBundle:Payment');
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
                    private $status;

                    public function __construct(string $route, string $status)
                    {
                        parent::__construct($route);

                        $this->status = $status;
                    }

                    public function getFlash(): iterable
                    {
                        yield from PaymentCompleteListener::addFlashMessage($this->status);
                    }
                }
            );
        }
    }

    /**
     * @param string $status
     *
     * @return iterable
     */
    public static function addFlashMessage(string $status): iterable
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
