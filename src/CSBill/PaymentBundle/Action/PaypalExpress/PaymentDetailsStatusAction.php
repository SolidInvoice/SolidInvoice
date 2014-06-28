<?php
namespace CSBill\PaymentBundle\Action\PaypalExpress;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\StatusRequestInterface;
use Payum\Core\Action\PaymentAwareAction;

class PaymentDetailsStatusAction extends PaymentAwareAction
{
    /**
     * @param \CSBill\PaymentBundle\Action\Request\StatusRequest $request
     *                                                                    {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request \Payum\Core\Request\StatusRequestInterface */
        if (false == $this->supports($request)) {
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
        if (false == $request instanceof StatusRequestInterface) {
            return false;
        }

        $model = $request->getModel();
        if (false == $model instanceof \ArrayAccess) {
            return false;
        }

        $message = $model->getPayment()->getMessage();

        return isset($model['PAYMENTREQUEST_0_AMT']) && null !== $model['PAYMENTREQUEST_0_AMT'] && empty($message);
    }
}
