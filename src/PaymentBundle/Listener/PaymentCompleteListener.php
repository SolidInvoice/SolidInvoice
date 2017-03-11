<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Listener;

use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use CSBill\PaymentBundle\Model\Status;
use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentCompleteListener implements EventSubscriberInterface
{
    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
	return [
	    PaymentEvents::PAYMENT_COMPLETE => 'onPaymentComplete',
	];
    }

    /**
     * @param InvoiceManager      $invoiceManager
     * @param ManagerRegistry     $registry
     * @param SessionInterface    $session
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     * @param Currency            $currency
     */
    public function __construct(
        InvoiceManager $invoiceManager,
        ManagerRegistry $registry,
        SessionInterface $session,
        TranslatorInterface $translator,
        RouterInterface $router,
        Currency $currency
    ) {
        $this->invoiceManager = $invoiceManager;
        $this->session = $session;
        $this->translator = $translator;
        $this->router = $router;
        $this->registry = $registry;
        $this->currency = $currency;
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentComplete(PaymentCompleteEvent $event)
    {
        $payment = $event->getPayment();
        $status = (string) $payment->getStatus();

        $this->addFlashMessage($status, $payment->getMessage());

        if ('credit' === $payment->getMethod()->getGatewayName()) {
            $creditRepository = $this->registry->getRepository('CSBillClientBundle:Credit');
            $creditRepository->deductCredit(
                $payment->getClient(),
                new Money($payment->getTotalAmount(), $this->currency)
            );
        }

        if (null !== $invoice = $event->getPayment()->getInvoice()) {
            if ($status === Status::STATUS_CAPTURED && $this->invoiceManager->isFullyPaid($invoice)) {
                $this->invoiceManager->pay($invoice);
            } else {
                $paymentRepository = $this->registry->getRepository('CSBillPaymentBundle:Payment');
                $invoiceTotal = $invoice->getTotal();
                $totalPaid = new Money($paymentRepository->getTotalPaidForInvoice($invoice), $invoiceTotal->getCurrency());
                $invoice->setBalance($invoiceTotal->subtract($totalPaid));

                $em = $this->registry->getManager();
                $em->persist($invoice);
                $em->flush();
            }

            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate('_view_invoice_external', ['uuid' => $invoice->getUuid()])
                )
            );
        }
    }

    /**
     * @param string $status
     * @param string $errorMessage
     */
    private function addFlashMessage($status, $errorMessage = null)
    {
        switch ($status) {
            case Status::STATUS_CAPTURED:
                $type = 'success';
                $message = 'payment.flash.status.success';
                break;

            case Status::STATUS_CANCELLED:
                $type = 'danger';
                $message = 'payment.flash.status.cancelled';
                break;

            case Status::STATUS_PENDING:
                $type = 'warning';
                $message = 'payment.flash.status.pending';
                break;

            case Status::STATUS_EXPIRED:
                $type = 'danger';
                $message = 'payment.flash.status.expired';
                break;

            case Status::STATUS_FAILED:
                $type = 'danger';
                $message = 'payment.flash.status.failed';
                break;

            case Status::STATUS_NEW:
                $type = 'warning';
                $message = 'payment.flash.status.new';
                break;

            case Status::STATUS_SUSPENDED:
                $type = 'danger';
                $message = 'payment.flash.status.suspended';
                break;

            case Status::STATUS_AUTHORIZED:
                $type = 'info';
                $message = 'payment.flash.status.authorized';
                break;

            case Status::STATUS_REFUNDED:
                $type = 'warning';
                $message = 'payment.flash.status.refunded';
                break;

            case Status::STATUS_UNKNOWN:
            default:
                $type = 'danger';
                $message = 'payment.flash.status.unknown';
                break;
        }

        /** @var FlashBag $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $parameters = ['%message%' => $errorMessage];

        $flashBag->add($type, $this->translator->trans($message, $parameters));
    }
}
