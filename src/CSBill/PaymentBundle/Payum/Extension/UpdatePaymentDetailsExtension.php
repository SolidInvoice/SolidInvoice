<?php

/*
 * This file is part of CSBill project.
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
use Payum\Core\Extension\Context;
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
    public function onPreExecute(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute(Context $context)
    {
        $action = $context->getAction();

        if ($action instanceof CaptureAction) {
            $request = $context->getRequest();
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
