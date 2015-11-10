<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\PaypalExpress;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use CSBill\PaymentBundle\Entity\Payment;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryInterface;

/**
 * @deprecated This action is not used anymore and will be removed in a future version
 */
class CapturePaymentAction extends GatewayAwareAction
{
    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @param GenericTokenFactoryInterface $tokenFactory
     * @param MoneyFormatter               $formatter
     */
    public function __construct(GenericTokenFactoryInterface $tokenFactory, MoneyFormatter $formatter)
    {
        $this->tokenFactory = $tokenFactory;
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();

        if ($payment->getDetails()) {
            return;
        }

        $invoice = $payment->getInvoice();

        $details = [];

        $details['PAYMENTREQUEST_0_INVNUM'] = $invoice->getId().'-'.$payment->getId();
        $details['PAYMENTREQUEST_0_CURRENCYCODE'] = $payment->getCurrencyCode();
        $details['PAYMENTREQUEST_0_AMT'] = number_format($this->formatter->toFloat($invoice->getTotal()), 2);
        $details['PAYMENTREQUEST_0_ITEMAMT'] = number_format($this->formatter->toFloat($invoice->getTotal()), 2);

        $counter = 0;
        foreach ($invoice->getItems() as $item) {
            /* @var \CSBill\InvoiceBundle\Entity\Item $item */

            $details['L_PAYMENTREQUEST_0_NAME'.$counter] = $item->getDescription();
            $details['L_PAYMENTREQUEST_0_AMT'.$counter] = number_format($this->formatter->toFloat($item->getPrice()), 2);
            $details['L_PAYMENTREQUEST_0_QTY'.$counter] = $item->getQty();

            ++$counter;
        }

        if (null !== $invoice->getDiscount()) {
            $discount = $invoice->getBaseTotal()->multiply($invoice->getDiscount());
            $details['L_PAYMENTREQUEST_0_NAME'.$counter] = 'Discount';
            $details['L_PAYMENTREQUEST_0_AMT'.$counter] = '-'.number_format($this->formatter->toFloat($discount), 2);
            $details['L_PAYMENTREQUEST_0_QTY'.$counter] = 1;
        }

        if (null !== $tax = $invoice->getTax()) {
            $details['L_PAYMENTREQUEST_0_NAME'.$counter] = 'Tax Total';
            $details['L_PAYMENTREQUEST_0_AMT'.$counter] = number_format($this->formatter->toFloat($tax), 2);
            $details['L_PAYMENTREQUEST_0_QTY'.$counter] = 1;
        }

        /*$notifyUrl = $this->tokenFactory->createNotifyToken(
            $request->getToken()->getGatewayName(),
            $payment
        )->getTargetUrl();

        $details['PAYMENTREQUEST_0_NOTIFYURL'] = $notifyUrl;*/

        $payment->setDetails($details);
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        try {
            $request->setModel($details);
            $this->gateway->execute($request);

            $payment->setDetails($details);
            $request->setModel($payment);
        } catch (\Exception $e) {
            $payment->setDetails($details);
            $request->setModel($payment);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        @trigger_error('This '.__CLASS__.' is not used anymore and will be removed in a future version', E_USER_DEPRECATED);

        if (!($request instanceof Capture && $request->getModel() instanceof Payment)) {
            return false;
        }

        /** @var Payment $payment */
        $payment = $request->getModel();

        if ($payment->getDetails()) {
            return false;
        }

        return true;
    }
}
