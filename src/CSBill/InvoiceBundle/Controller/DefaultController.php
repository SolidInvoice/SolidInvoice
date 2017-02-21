<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * List all the invoices.
     *
     * @return Response
     */
    public function indexAction()
    {
        /** @var \CSBill\InvoiceBundle\Repository\InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Invoice');

        return $this->render(
            'CSBillInvoiceBundle:Default:index.html.twig',
            [
                'status_list_count' => [
                    Graph::STATUS_PENDING => $invoiceRepository->getCountByStatus(Graph::STATUS_PENDING),
                    Graph::STATUS_PAID => $invoiceRepository->getCountByStatus(Graph::STATUS_PAID),
                    Graph::STATUS_CANCELLED => $invoiceRepository->getCountByStatus(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $invoiceRepository->getCountByStatus(Graph::STATUS_DRAFT),
                    Graph::STATUS_OVERDUE => $invoiceRepository->getCountByStatus(Graph::STATUS_OVERDUE),
                ],
                'total_income' => $this->getRepository('CSBillPaymentBundle:Payment')->getTotalIncome(),
                'total_outstanding' => $invoiceRepository->getTotalOutstanding(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return Response
     */
    public function createAction(Request $request, Client $client = null)
    {
        /** @var \CSBill\ClientBundle\Repository\ClientRepository $clients */
        $clients = $this->getRepository('CSBillClientBundle:Client');

        if (!$clients->getTotalClients() > 0) {
            return $this->render('CSBillInvoiceBundle:Default:empty_clients.html.twig');
        }

        $invoice = new Invoice();
        $invoice->setClient($client);

        $options = [];

        if ($client && $client->getCurrency()) {
            $options['currency'] = $client->getCurrency();
        }

        $form = $this->createForm(InvoiceType::class, $invoice, $options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $invoiceManager = $this->get('invoice.manager');

            $invoice->setBalance($invoice->getTotal());

            $invoice = $invoiceManager->create($invoice);

            if ($action === Graph::STATUS_PENDING) {
                $invoiceManager->accept($invoice);
            }

            $this->flash($this->trans('invoice.create.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', ['id' => $invoice->getId()]));
        }

        return $this->render('CSBillInvoiceBundle:Default:create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return Response
     */
    public function editAction(Request $request, Invoice $invoice)
    {
        if ($invoice->getStatus() === Graph::STATUS_PAID) {
            $this->flash($this->trans('invoice.edit.paid'), 'warning');

            return $this->redirectToRoute('_invoices_index');
        }

        $form = $this->createForm(InvoiceType::class, $invoice, ['currency' => $invoice->getClient()->getCurrency()]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $invoiceManager = $this->get('invoice.manager');

            /** @var PaymentRepository $paymentRepository */
            $paymentRepository = $this->getRepository('CSBillPaymentBundle:Payment');
            $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

            $invoice->setBalance($invoice->getTotal()->subtract(new Money($totalPaid, $invoice->getTotal()->getCurrency())));

            if ($action === Graph::STATUS_PENDING) {
                $invoiceManager->accept($invoice);
            } else {
                $this->save($invoice);
            }

            $this->flash($this->trans('invoice.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', ['id' => $invoice->getId()]));
        }

        return $this->render(
            'CSBillInvoiceBundle:Default:edit.html.twig',
            [
                'form' => $form->createView(),
                'invoice' => $invoice,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getFieldsAction(Request $request)
    {
        $form = $this->createForm(InvoiceType::class, null, ['currency' => new Currency($request->get('currency'))]);

        return $this->json($this->get('csbill_core.field.renderer')->render($form->createView(), 'children[items].vars[prototype]'));
    }

    /**
     * View a Invoice.
     *
     * @param Invoice $invoice
     *
     * @return Response
     */
    public function viewAction(Invoice $invoice)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->getRepository('CSBillPaymentBundle:Payment');
        $payments = $paymentRepository->getPaymentsForInvoice($invoice);

        return $this->render(
            'CSBillInvoiceBundle:Default:view.html.twig',
            [
                'invoice' => $invoice,
                'payments' => $payments,
            ]
        );
    }

    /**
     * @param Invoice $invoice
     *
     * @return RedirectResponse
     */
    public function cloneAction(Invoice $invoice)
    {
        $newInvoice = $this->get('invoice.manager')->duplicate($invoice);

        $this->flash($this->trans('invoice.clone.success'), 'success');

        return $this->redirectToRoute('_invoices_view', ['id' => $newInvoice->getId()]);
    }
}
