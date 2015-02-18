<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Listener;

use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Model\Status;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentCompleteListener
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
     * @param InvoiceManager      $invoiceManager
     * @param SessionInterface    $session
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     */
    public function __construct(
        InvoiceManager $invoiceManager,
        SessionInterface $session,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->invoiceManager = $invoiceManager;
        $this->session = $session;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentComplete(PaymentCompleteEvent $event)
    {
        $payment = $event->getPayment();
        $status = (string) $payment->getStatus();

        $this->addFlashMessage($status, $payment->getMessage());

        if (null !== $invoice = $event->getPayment()->getInvoice()) {
            if ($status === Status::STATUS_CAPTURED) {
                $this->invoiceManager->pay($invoice);
            }

            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate('_view_invoice_external', array('uuid' => $invoice->getUuid()))
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
        $parameters = array('%message%' => $errorMessage);

        $flashBag->add($type, $this->translator->trans($message, $parameters));
    }
}