<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\PaymentAction\PaypalExpress;

use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\PaymentAction\Request\StatusRequest;

class PaymentDetailsStatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function execute($request): void
    {
        /** @var StatusRequest $request */
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();

        if (! $payment instanceof Payment) {
            $payment = $request->getFirstModel();
        }

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $message = [];

        foreach (range(0, 9) as $index) {
            if ($details['L_ERRORCODE' . $index]) {
                $message[] = $details['L_LONGMESSAGE' . $index];
            }
        }

        $payment->setMessage(implode('. ', $message));

        if ($payment->getDetails()) {
            try {
                $request->setModel($details);
                $this->gateway->execute($request);

                $payment->setDetails($details);
                $request->setModel($payment);
            } catch (Exception $e) {
                $payment->setDetails($details);
                $request->setModel($payment);

                throw $e;
            }
        } else {
            $request->markNew();
        }
    }

    public function supports($request)
    {
        return $request instanceof StatusRequest &&
            $request->getModel() instanceof PaymentInterface;
    }
}
