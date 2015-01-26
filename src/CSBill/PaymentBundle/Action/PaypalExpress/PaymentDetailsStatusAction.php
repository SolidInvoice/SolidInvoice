<?php
namespace CSBill\PaymentBundle\Action\PaypalExpress;

use CSBill\PaymentBundle\Action\Request\StatusRequest;
use CSBill\PaymentBundle\Entity\PaymentDetails;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class PaymentDetailsStatusAction extends PaymentAwareAction
{
    /**
     * @param \CSBill\PaymentBundle\Action\Request\StatusRequest $request
     *                                                                    {@inheritdoc}
     */
    public function execute($request)
    {
        if (false === $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $model = ArrayObject::ensureArrayObject($request->getModel());

        foreach (range(0, 9) as $index) {
            if ($model['L_ERRORCODE'.$index]) {
                $request->getModel()->getPayment()->setMessage($model['L_LONGMESSAGE'.$index]);

                $this->payment->execute($request);

                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        if (!$request instanceof StatusRequest) {
            return false;
        }

        $model = $request->getModel();

        if (!$model instanceof PaymentDetails) {
            return false;
        }

        $message = $model->getPayment()->getMessage();

        return isset($model['PAYMENTREQUEST_0_AMT']) && null !== $model['PAYMENTREQUEST_0_AMT'] && empty($message);
    }
}
