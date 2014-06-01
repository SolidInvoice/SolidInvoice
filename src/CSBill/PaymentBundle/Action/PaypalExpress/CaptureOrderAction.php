<?php

namespace CSBill\PaymentBundle\Action\PaypalExpress;

use CSBill\PaymentBundle\Entity\PaymentDetails;
use Payum\Bundle\PayumBundle\Security\TokenFactory;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\SecuredCaptureRequest;

class CaptureOrderAction extends PaymentAwareAction
{
    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param TokenFactory $tokenFactory
     * @param string       $currency
     */
    public function __construct(TokenFactory $tokenFactory, $currency)
    {
        $this->tokenFactory = $tokenFactory;
        $this->currency = $currency;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request SecuredCaptureRequest */
        if (!$this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        /** @var PaymentDetails $model */
        $model = $request->getModel();

        $details = array();

        /** @var \CSBill\InvoiceBundle\Entity\Invoice $invoice */
        $invoice = $model->getPayment()->getInvoice();

        if (0 === count($model->getIterator())) {
            $details['PAYMENTREQUEST_0_CURRENCYCODE'] = $this->currency;

            $details['PAYMENTREQUEST_0_INVNUM'] = $invoice->getId();
            $details['PAYMENTREQUEST_0_AMT'] = number_format($invoice->getTotal(), 2);
            $details['PAYMENTREQUEST_0_ITEMAMT'] = number_format($invoice->getTotal(), 2);

            $counter = 0;
            foreach ($invoice->getItems() as $item) {
                /** @var \CSBill\InvoiceBundle\Entity\Item $item */

                $details['L_PAYMENTREQUEST_0_NAME'.$counter] = $item->getDescription();
                $details['L_PAYMENTREQUEST_0_AMT'.$counter] = number_format($item->getTotal() / $item->getQty(), 2);
                $details['L_PAYMENTREQUEST_0_QTY'.$counter] = $item->getQty();

                $counter++;
            }

            if (null !== $invoice->getDiscount()) {
                $discount = ($invoice->getBaseTotal() * $invoice->getDiscount());
                $details['L_PAYMENTREQUEST_0_NAME'.$counter] = 'Discount';
                $details['L_PAYMENTREQUEST_0_AMT'.$counter]  = number_format($discount, 2) * -1;
                $details['L_PAYMENTREQUEST_0_QTY'.$counter]  = 1;
            }

            $details['INVNUM'] = $invoice->getId();
            $details['RETURNURL'] = $request->getToken()->getTargetUrl();
            $details['CANCELURL'] = $request->getToken()->getTargetUrl();

            $details['PAYMENTREQUEST_0_NOTIFYURL'] = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getPaymentName(),
                $request->getModel()
            )->getTargetUrl();

            $model->setDetails($details);

            $request->setModel($model);

            $this->payment->execute($request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof SecuredCaptureRequest &&
            $request->getModel() instanceof PaymentDetails &&
            !$request->getModel()->getDetails()
            ;
    }
}
