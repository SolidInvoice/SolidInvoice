<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\PaymentBundle\Action\PaypalExpress;

use CSBill\PaymentBundle\Action\Request\StatusRequest;
use CSBill\PaymentBundle\Entity\Payment;
use Doctrine\Common\Persistence\ObjectManager;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Notify;
use Payum\Core\Request\Sync;

class NotifyPaymentAction extends PaymentAwareAction
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();

        $this->payment->execute(new Sync($payment));

        $status = new StatusRequest($payment);
        $this->payment->execute($status);

        $nextState = $status->getValue();

        $payment->setStatus($nextState);

        $this->objectManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getToken() &&
            $request->getModel() instanceof Payment
        ;
    }
}
