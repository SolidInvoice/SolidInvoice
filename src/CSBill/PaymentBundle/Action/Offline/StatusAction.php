<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\Offline;

use CSBill\PaymentBundle\Action\Request\StatusRequest;
use CSBill\PaymentBundle\Entity\Payment;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Offline\Constants;

class StatusAction extends GatewayAwareAction
{
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment->getDetails() ?: array());

        $details[Constants::FIELD_STATUS] = Constants::STATUS_CAPTURED;

        $payment->setDetails($details);

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
        return
            $request instanceof StatusRequest &&
            $request->getModel() instanceof Payment
        ;
    }
}
