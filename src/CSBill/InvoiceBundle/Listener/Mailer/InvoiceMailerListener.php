<?php

namespace CSBill\InvoiceBundle\Listener\Mailer;

use CSBill\CoreBundle\Mailer\Events\InvoiceEvent;
use CSBill\PaymentBundle\Manager\PaymentMethodManager;
use Symfony\Component\Templating\EngineInterface;

class InvoiceMailerListener
{
    const TEMPLATE = 'CSBillInvoiceBundle:Email:payment.html.twig';

    /**
     * @var PaymentMethodManager
     */
    private $paymentManager;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @param PaymentMethodManager $paymentManager
     */
    public function __construct(PaymentMethodManager $paymentManager, EngineInterface $templating)
    {
        $this->paymentManager = $paymentManager;
        $this->templating = $templating;
    }

    public function onInvoiceMail(InvoiceEvent $event)
    {
        if (count($this->paymentManager) > 0) {
            $htmlTemplate = $event->getHtmlTemplate();

            $htmlTemplate .= $this->templating->render(self::TEMPLATE, array('invoice' => $event->getInvoice()));

            $event->setHtmlTemplate($htmlTemplate);
        }
    }
}
