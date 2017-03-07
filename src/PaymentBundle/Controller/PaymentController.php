<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Action\Request\StatusRequest;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\PaymentBundle\Entity\PaymentMethod as Entity;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use CSBill\PaymentBundle\Form\Type\PaymentType;
use CSBill\PaymentBundle\Model\Status;
use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
use Money\Money;
use Payum\Core\Model\Token;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends BaseController
{
    /**
     * @ParamConverter("invoice")
     *
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function preparePaymentAction(Request $request, Invoice $invoice = null)
    {
        if (null === $invoice) {
            throw $this->createNotFoundException();
        }

        $finite = $this->get('finite.factory')->get($invoice, Graph::GRAPH);

        if (!$finite->can(Graph::TRANSITION_PAY)) {
            throw new \Exception('This invoice cannot be paid');
        }

        /** @var PaymentMethodRepository $paymentRepository */
        $paymentRepository = $this->getRepository('CSBillPaymentBundle:PaymentMethod');

        if ($paymentRepository->getTotalMethodsConfigured($this->isGranted('ROLE_SUPER_ADMIN')) < 1) {
            throw new \Exception('No payment methods available');
        }

        $preferredChoices = $paymentRepository
            ->findBy(['gatewayName' => 'credit']);

        $currency = $invoice->getClient()->getCurrency();
        $form = $this->createForm(
            PaymentType::class,
            [
                'amount' => $invoice->getBalance(),
            ],
            [
                'user' => $this->getUser(),
                'currency' => $currency ? $currency->getCode() : $this->getParameter('currency'),
                'preferred_choices' => $preferredChoices,
            ]
        );

        $form->handleRequest($request);

        $paymentFactories = array_keys($this->get('payum.factories')->getFactories('offline'));

        if ($form->isValid()) {
            $data = $form->getData();
            /** @var Money $amount */
            $amount = $data['amount'];

            /** @var Entity $paymentMethod */
            $paymentMethod = $data['payment_method'];

            $paymentName = $paymentMethod->getGatewayName();

            if (in_array($paymentName, $paymentFactories)) {
                if ('credit' === $paymentName) {
                    $clientCredit = $invoice->getClient()->getCredit()->getValue();

                    $invalid = '';
                    if ($amount->greaterThan($clientCredit)) {
                        $invalid = 'payment.create.exception.not_enough_credit';
                    } elseif ($amount->greaterThan($invoice->getBalance())) {
                        $invalid = 'payment.create.exception.amount_exceeds_balance';
                    }

                    if (!empty($invalid)) {
                        $this->flash($this->trans($invalid), 'error');

                        return $this->render(
                            'CSBillPaymentBundle:Payment:create.html.twig',
                            [
                                'form' => $form->createView(),
                                'invoice' => $invoice,
                                'internal' => array_keys($paymentFactories),
                            ]
                        );
                    }
                }

                $data['capture_online'] = true;
            }

            $payment = new Payment();
            $payment->setInvoice($invoice);
            $payment->setStatus(Status::STATUS_NEW);
            $payment->setMethod($data['payment_method']);
            /** @var \Money\Money $money */
            $money = $data['amount'];
            $payment->setTotalAmount($money->getAmount());
            $payment->setCurrencyCode($money->getCurrency()->getCode());
            $payment->setDescription('');
            $payment->setClient($invoice->getClient());
            $payment->setNumber($invoice->getId());
            $payment->setClientEmail($invoice->getClient()->getContacts()->first()->getEmail());
            $invoice->addPayment($payment);
            $this->save($payment);

            if (array_key_exists('capture_online', $data) && true === $data['capture_online']) {
                $captureToken = $this->get('payum')
                    ->getTokenFactory()
                    ->createCaptureToken(
                        $paymentName,
                        $payment,
                        '_payments_done' // the route to redirect after capture;
                    );

                return $this->redirect($captureToken->getTargetUrl());
            } else {
                $payment->setStatus(Status::STATUS_CAPTURED);
                $payment->setCompleted(new \DateTime('now'));
                $this->save($payment);

                $event = new PaymentCompleteEvent($payment);
                $this->get('event_dispatcher')->dispatch(PaymentEvents::PAYMENT_COMPLETE, $event);

                if ($response = $event->getResponse()) {
                    return $response;
                }

                return $this->redirectToRoute('_payments_index');
            }
        }

        return $this->render(
            'CSBillPaymentBundle:Payment:create.html.twig',
            [
                'form' => $form->createView(),
                'invoice' => $invoice,
                'internal' => $paymentFactories,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function captureDoneAction(Request $request)
    {
        /** @var Token $token */
        $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);

        $paymentMethod = $this->get('payum')->getGateway($token->getGatewayName());
        $paymentMethod->execute($status = new StatusRequest($token));

        /** @var \CSBill\PaymentBundle\Entity\Payment $payment */
        $payment = $status->getFirstModel();

        $payment->setStatus($status->getValue());
        $payment->setCompleted(new \DateTime('now'));

        $this->save($payment);

        $event = new PaymentCompleteEvent($payment);
        $this->get('event_dispatcher')->dispatch(PaymentEvents::PAYMENT_COMPLETE, $event);

        if ($response = $event->getResponse()) {
            return $response;
        }

        return $this->redirectToRoute('_payments_index');
    }
}
