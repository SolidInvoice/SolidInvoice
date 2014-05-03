<?php


namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\PaymentBundle\Entity\PaymentDetails;
use CSBill\PaymentBundle\Entity\Status;
use Symfony\Component\HttpFoundation\Request;
use CSBill\PaymentBundle\Action\Request\StatusRequest;

class PaymentController extends BaseController
{
    /**
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function preparePaymentAction(Request $request, Invoice $invoice)
    {
        if ('pending' !== (string) $invoice->getStatus()) {
            throw new \Exception('This invoice cannot be paid');
        }

        $paymentManager = $this->get('csbill_payment.method.manager');

        $builder = $this->createFormBuilder();

        $builder->add('payment_method', 'entity', array('class' => 'CSBillPaymentBundle:PaymentMethod'));

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $paymentMethod = $paymentManager->getPaymentMethod($data['payment_method']->getPaymentMethod());

            $paymentName = $paymentMethod->getContext();

            $status = $this->getRepository('CSBillPaymentBundle:Status')->findOneBy(array('name' => Status::STATUS_NEW));
            $details = new PaymentDetails;
            /** @var \CSBill\PaymentBundle\Entity\PaymentDetails $paymentDetails */
            $invoice->addPayment($details);
            $details->setInvoice($invoice);
            $details->setStatus($status);

            $entityManager = $this->getEm();
            $entityManager->persist($details);
            $entityManager->flush();

            $captureToken = $this->get('payum.security.token_factory')->createCaptureToken(
                $paymentName,
                $details,
                '_payments_done' // the route to redirect after capture;
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render(
            'CSBillPaymentBundle:Payment:create.html.twig',
            array(
                'form'    => $form->createView(),
                'invoice' => $invoice
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function captureDoneAction(Request $request)
    {
        $entityManager = $this->getEm();

        /** @var \CSBill\PaymentBundle\Entity\SecurityToken $token */
        $token = $this->get('payum.security.http_request_verifier')->verify($request);

        /** @var \Payum\Core\Payment $payment */
        $payment = $this->get('payum')->getPayment($token->getPaymentName());

        $status = new StatusRequest($token);
        $payment->execute($status);

        $paymentDetails = $status->getModel();

        $paymentDetails->setStatus(
            $this
                ->getRepository('CSBillPaymentBundle:Status')
                ->findOneBy(array('name' => $status->getStatus()))
        );

        $entityManager->persist($paymentDetails);

        /** @var \CSBill\InvoiceBundle\Entity\Invoice $invoice */
        $invoice = $paymentDetails->getInvoice();

        if ($status->isSuccess()) {

            $invoice->setPaidDate(new \DateTime('NOW'));
            $invoice->setStatus(
                $this
                    ->getRepository('CSBillInvoiceBundle:Status')
                    ->findOneBy(array('name' => 'paid'))
            );
            $entityManager->persist($invoice);
            $this->flash('Payment success.', 'success');
        } else if ($status->isPending()) {
            $this->flash('Payment is still pending.', 'warning');
        } else {
            $this->flash('Payment failed.', 'error');
        }

        $entityManager->flush();

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }
} 