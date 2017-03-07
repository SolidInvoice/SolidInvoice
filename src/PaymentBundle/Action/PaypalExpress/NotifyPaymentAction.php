<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\PaypalExpress;

use CSBill\PaymentBundle\Action\Request\StatusRequest;
use CSBill\PaymentBundle\Entity\Payment;
use Doctrine\Common\Persistence\ObjectManager;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Payum\Core\Request\Sync;

/**
 * @deprecated This action is not used anymore and will be removed in a future version
 */
class NotifyPaymentAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

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
     * {@inheritdoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
	RequestNotSupportedException::assertSupports($this, $request);

	/** @var Payment $payment */
	$payment = $request->getModel();

	$this->gateway->execute(new Sync($payment));

	$status = new StatusRequest($payment);
	$this->gateway->execute($status);

	$nextState = $status->getValue();

	$payment->setStatus($nextState);

	$this->objectManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
	@trigger_error('This '.__CLASS__.' is not used anymore and will be removed in a future version', E_USER_DEPRECATED);

	return
	    $request instanceof Notify &&
	    $request->getToken() &&
	    $request->getModel() instanceof Payment;
    }
}
