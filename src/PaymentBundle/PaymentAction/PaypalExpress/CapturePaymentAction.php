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

use Brick\Math\Exception\MathException;
use Exception;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\PaymentBundle\Entity\Payment;
use function assert;

/**
 * @deprecated This action is not used anymore and will be removed in a future version
 */
class CapturePaymentAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    public function __construct(
        GenericTokenFactoryInterface $tokenFactory,
    ) {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * @throws MathException
     * @throws Exception
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var Payment $payment */
        $payment = $request->getModel();

        if ($payment->getDetails()) {
            return;
        }

        $invoice = $payment->getInvoice();
        assert($invoice instanceof Invoice);

        $details = [];

        $details['PAYMENTREQUEST_0_INVNUM'] = $invoice->getId() . '-' . $payment->getId();
        $details['PAYMENTREQUEST_0_CURRENCYCODE'] = $payment->getCurrencyCode();
        $details['PAYMENTREQUEST_0_AMT'] = number_format(MoneyFormatter::toFloat($invoice->getTotal()), 2);
        $details['PAYMENTREQUEST_0_ITEMAMT'] = number_format(MoneyFormatter::toFloat($invoice->getTotal()), 2);

        $counter = 0;
        foreach ($invoice->getItems() as $item) {
            /** @var Item $item */
            $details['L_PAYMENTREQUEST_0_NAME' . $counter] = $item->getDescription();
            $details['L_PAYMENTREQUEST_0_AMT' . $counter] = number_format(MoneyFormatter::toFloat($item->getPrice()), 2);
            $details['L_PAYMENTREQUEST_0_QTY' . $counter] = $item->getQty();

            ++$counter;
        }

        if ($invoice->getDiscount()->getValue()) {
            $discount = $invoice->getBaseTotal()->toBigDecimal()->multipliedBy($invoice->getDiscount()->getValue());
            $details['L_PAYMENTREQUEST_0_NAME' . $counter] = 'Discount';
            $details['L_PAYMENTREQUEST_0_AMT' . $counter] = '-' . number_format(MoneyFormatter::toFloat($discount), 2);
            $details['L_PAYMENTREQUEST_0_QTY' . $counter] = 1;
        }

        if (null !== $tax = $invoice->getTax()) {
            $details['L_PAYMENTREQUEST_0_NAME' . $counter] = 'Tax Total';
            $details['L_PAYMENTREQUEST_0_AMT' . $counter] = number_format(MoneyFormatter::toFloat($tax), 2);
            $details['L_PAYMENTREQUEST_0_QTY' . $counter] = 1;
        }

        $payment->setDetails($details);
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

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
    }

    public function supports($request)
    {
        @trigger_error('This ' . self::class . ' is not used anymore and will be removed in a future version', E_USER_DEPRECATED);

        if (! ($request instanceof Capture && $request->getModel() instanceof Payment)) {
            return false;
        }

        /** @var Payment $payment */
        $payment = $request->getModel();

        return ! $payment->getDetails();
    }
}
