<?php

namespace CSBill\PaymentBundle\Listener;

use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentSubscriber implements EventSubscriberInterface
{
    /**
     * @var \CSBill\InvoiceBundle\Manager\InvoiceManager
     */
    protected $manager;

    /**
     * @param InvoiceManager $manager
     */
    public function __construct(InvoiceManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            PaymentEvents::PAYMENT_COMPLETE => array('onPaymentComplete')
        );
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentComplete(PaymentCompleteEvent $event)
    {
        $payment = $event->getPayment();

        if ('paid' === (string) $payment->getStatus()) {
            $this->manager->markPaid($payment->getInvoice());
        }
    }
}
