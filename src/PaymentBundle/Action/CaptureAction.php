<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action;

use CSBill\PaymentBundle\Entity\Payment;
use CSBill\PaymentBundle\Model\Status;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Offline\Constants;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
	RequestNotSupportedException::assertSupports($this, $request);

	/** @var Payment $payment */
	$payment = $request->getModel();
	$details = ArrayObject::ensureArrayObject($payment->getDetails());

	$details[Constants::FIELD_STATUS] = Status::STATUS_NEW;
	$payment->setDetails($details);

	$request->setModel($details);

	$this->gateway->execute($request);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
	if (!($request instanceof Capture && $request->getModel() instanceof Payment)) {
	    return false;
	}

	$details = ArrayObject::ensureArrayObject($request->getModel()->getDetails());

	return null === $details[Constants::FIELD_STATUS];
    }
}
