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

namespace SolidInvoice\PaymentBundle\Payum\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Paypal\ExpressCheckout\Nvp\Action\CaptureAction;
use SolidInvoice\PaymentBundle\Entity\Payment;

class UpdatePaymentDetailsExtension implements ExtensionInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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

            $em = $this->registry->getManager();

            $em->persist($payment);
            $em->flush();
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
    public function onException(Exception $exception, $request, ActionInterface $action = null)
    {
    }
}
