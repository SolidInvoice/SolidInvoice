<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\DataGridBundle\Grid\GridCollection;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
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
        $gridCollection = new GridCollection();
        $gridCollection->add('csbill.invoice.grid.default_grid', 'active', 'check');
        $gridCollection->add('csbill.invoice.grid.archived_grid', 'archived', 'archive');

        $grid = $this->get('grid')->create($gridCollection);

        /** @var \CSBill\InvoiceBundle\Repository\InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Invoice');

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            array(
                'status_list' => array(
                    Graph::STATUS_PENDING,
                    Graph::STATUS_PAID,
                    Graph::STATUS_CANCELLED,
                    Graph::STATUS_DRAFT,
                    Graph::STATUS_OVERDUE,
                ),
                'status_list_count' => array(
                    Graph::STATUS_PENDING => $invoiceRepository->getCountByStatus(Graph::STATUS_PENDING),
                    Graph::STATUS_PAID => $invoiceRepository->getCountByStatus(Graph::STATUS_PAID),
                    Graph::STATUS_CANCELLED => $invoiceRepository->getCountByStatus(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $invoiceRepository->getCountByStatus(Graph::STATUS_DRAFT),
                    Graph::STATUS_OVERDUE => $invoiceRepository->getCountByStatus(Graph::STATUS_OVERDUE),
                ),
                'total_income' => $invoiceRepository->getTotalIncome(),
                'total_outstanding' => $invoiceRepository->getTotalOutstanding(),
            )
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

        $form = $this->createForm('invoice', $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $invoiceManager = $this->get('invoice.manager');

            $invoice->setBalance($invoice->getTotal());

            $invoiceManager->create($invoice);

            if ($action === Graph::STATUS_PENDING) {
                $invoiceManager->accept($invoice);
            }

            $this->flash($this->trans('invoice.create.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
        }

        return $this->render('CSBillInvoiceBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return Response
     */
    public function editAction(Request $request, Invoice $invoice)
    {
        $form = $this->createForm('invoice', $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $invoiceManager = $this->get('invoice.manager');

            /** @var PaymentRepository $paymentRepository */
            $paymentRepository = $this->getRepository('CSBillPaymentBundle:Payment');
            $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

            // @TODO: If current invoice total is updated to less than the total amount paid,
            // then the balance needs to be added as credit
            $invoice->setBalance($invoice->getTotal() - $totalPaid);

            if ($action === Graph::STATUS_PENDING) {
                $invoiceManager->accept($invoice);
            } else {
                $this->save($invoice);
            }

            $this->flash($this->trans('invoice.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
        }

        return $this->render(
            'CSBillInvoiceBundle:Default:edit.html.twig',
            array(
                'form' => $form->createView(),
                'invoice' => $invoice,
            )
        );
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
            array(
                'invoice' => $invoice,
                'payments' => $payments,
            )
        );
    }
}
