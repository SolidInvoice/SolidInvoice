<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Payum\Extension;

use CSBill\PaymentBundle\Entity\Payment;
use Doctrine\Common\Persistence\ObjectManager;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Action\CaptureAction;

class UpdatePaymentDetailsExtension implements ExtensionInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute($request)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute($request, ActionInterface $action)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute($request, ActionInterface $action)
    {
        /* @var \Payum\Core\Request\Capture $request */
        if ($action instanceof CaptureAction) {
            /** @var Payment $payment */
            $payment = $request->getFirstModel();
            $details = $request->getModel();

            $payment->setDetails($details);

            $this->objectManager->persist($payment);
            $this->objectManager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onReply(ReplyInterface $reply, $request, ActionInterface $action)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onException(\Exception $exception, $request, ActionInterface $action = null)
    {
    }
}
