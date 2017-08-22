<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\PaymentAction\PaypalExpress;

use SolidInvoice\PaymentBundle\PaymentAction\Request\StatusRequest;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;

class PaymentDetailsStatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /* @var StatusRequest $request */
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();

        if (!$payment instanceof Payment) {
            $payment = $request->getFirstModel();
        }

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $message = [];

        foreach (range(0, 9) as $index) {
            if ($details['L_ERRORCODE'.$index]) {
                $message[] = $details['L_LONGMESSAGE'.$index];
            }
        }

        $payment->setMessage(implode('. ', $message));

        if ($payment->getDetails()) {
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
        } else {
            $request->markNew();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof StatusRequest &&
            $request->getModel() instanceof PaymentInterface;
    }
}
