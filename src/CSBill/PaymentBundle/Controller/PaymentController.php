<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
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
use CSBill\PaymentBundle\Entity\Status;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use CSBill\PaymentBundle\Form\PaymentForm;
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

        $form = $this->createForm(new PaymentForm(), null, array('user' => $this->getUser()));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            /** @var Entity $paymentMethod */
            $paymentMethod = $data['payment_method'];

            $paymentName = $paymentMethod->getPaymentMethod();

            $status = $this
                ->getRepository('CSBillPaymentBundle:Status')
                ->findOneBy(array('name' => Status::STATUS_NEW));

            $payment = new Payment();
            $payment->setInvoice($invoice);
            $payment->setStatus($status);
            $payment->setMethod($data['payment_method']);
            $payment->setTotalAmount($invoice->getTotal());
            $payment->setCurrencyCode($this->container->getParameter('currency'));
            $payment->setClient($invoice->getClient());
            $invoice->addPayment($payment);

            $this->save($payment);

            $captureToken = $this->get('payum.security.token_factory')->createCaptureToken(
                $paymentName,
                $payment,
                '_payments_done' // the route to redirect after capture;
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render(
            'CSBillPaymentBundle:Payment:create.html.twig',
            array(
                'form' => $form->createView(),
                'invoice' => $invoice,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function captureDoneAction(Request $request)
    {
        $token = $this->get('payum.security.http_request_verifier')->verify($request);

        $paymentMethod = $this->get('payum')->getPayment($token->getPaymentName());
        $paymentMethod->execute($status = new StatusRequest($token));

        /** @var \CSBill\PaymentBundle\Entity\Payment $payment */
        $payment = $status->getFirstModel();

        $payment->setStatus(
            $this
                ->getRepository('CSBillPaymentBundle:Status')
                ->findOneBy(array('name' => $status->getValue()))
        );

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
