<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * List all the invoices
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('CSBillInvoiceBundle:Invoice');

        // Get a Grid instance
        $grid = $this->get('grid');
        $translator = $this->get('translator');
        $templating = $this->get('templating');

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search) {
            if ($search) {
                $queryBuilder->andWhere('_client.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $rowActicons = array();

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_invoices_edit');
        $editAction->addAttribute('title', $translator->trans('invoice.action.edit'));
        $editAction->addAttribute('rel', 'tooltip');
        $rowActicons[] = $editAction;

        $viewIcon = $templating->render('{{ icon("eye") }}');
        $viewAction = new RowAction($viewIcon, '_invoices_view');
        $viewAction->addAttribute('title', $translator->trans('invoice.action.view'));
        $viewAction->addAttribute('rel', 'tooltip');
        $rowActicons[] = $viewAction;

        $payIcon = $templating->render('{{ icon("credit-card") }}');
        $payAction = new RowAction($payIcon, '_payments_create');

        $payAction->setRouteParameters(array('uuid'));
        $payAction->setRouteParametersMapping(array('uuid' => 'uuid'));
        $payAction->addAttribute('title', $translator->trans('invoice.action.pay_now'));
        $payAction->addAttribute('rel', 'tooltip');

        $payAction->manipulateRender(function (RowAction $rowAction, Row $row) {
            if (Graph::STATUS_PENDING !== $row->getField('status')) {
                    return null;
            }

            return $rowAction;
        });
        $rowActicons[] = $payAction;

        $actionsRow = new ActionsColumn('actions', 'Action', $rowActicons);
        $grid->addColumn($actionsRow, 100);

        $grid->getColumn('discount')->manipulateRenderCell(function ($value) {
            if (!empty($value)) {
                return $value * 100 .'%';
            }

            return (int) $value;
        });

        $grid->setPermanentFilters(
            array(
                'client.name' => array('operator' => 'isNotNull'),
            )
        );

        /** @var \CSBill\InvoiceBundle\Repository\InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Invoice');

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'CSBillInvoiceBundle:Default:index.html.twig',
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
     * View a Invoice
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
