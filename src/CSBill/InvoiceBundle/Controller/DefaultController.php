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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * List all the invoices
     *
     * @param  Request  $request
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
        $editAction->addAttribute('title', $translator->trans('invoice_edit'));
        $editAction->addAttribute('rel', 'tooltip');
        $rowActicons[] = $editAction;

        $viewIcon = $templating->render('{{ icon("eye") }}');
        $viewAction = new RowAction($viewIcon, '_invoices_view');
        $viewAction->addAttribute('title', $translator->trans('invoice.view'));
        $viewAction->addAttribute('rel', 'tooltip');
        $rowActicons[] = $viewAction;

        // Only show payment option of payment methods is configured
        if (count($this->get('csbill_payment.method.manager')) > 0) {
            $payIcon = $templating->render('{{ icon("credit-card") }}');
            $payAction = new RowAction($payIcon, '_payments_create');

            $payAction->setRouteParameters(array('uuid'));
            $payAction->setRouteParametersMapping(array('uuid' => 'uuid'));
            $payAction->addAttribute('title', $translator->trans('invoice.pay_now'));
            $payAction->addAttribute('rel', 'tooltip');

            $payAction->manipulateRender(function (RowAction $rowAction, Row $row) {
                if ('pending' !== $row->getField('status.name')) {
                    $rowAction->setTitle('');
                }

                return $rowAction;
            });
            $rowActicons[] = $payAction;
        }

        $actionsRow = new ActionsColumn('actions', 'Action', $rowActicons);
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deletedAt', 'users', 'paidDate', 'due', 'baseTotal', 'uuid'));

        $grid->getColumn('total')->setCurrencyCode($this->container->getParameter('currency'));
        $grid->getColumn('tax')->setCurrencyCode($this->container->getParameter('currency'));
        $grid->getColumn('status.name')->manipulateRenderCell(function ($value, Row $row) {
            $label = $row->getField('status.label');

            return '<span class="label label-' . $label . '">' . ucfirst($value) . '</span>';
        })->setSafe(false);

        $grid->getColumn('discount')->manipulateRenderCell(function ($value) {
            if (!empty($value)) {
                return $value * 100 . '%';
            }

            return (int) $value;
        });

        $grid->setPermanentFilters(array(
            'client.name' => array('operator' => 'isNotNull'),
        ));

        $statusList = $this->getRepository('CSBillInvoiceBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'CSBillInvoiceBundle:Default:index.html.twig',
            array(
                'status_list' => $statusList,
            )
        );
    }

    /**
     * @param  Request  $request
     * @param  Client   $client
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
            $this->saveInvoice($invoice, $action);

            $this->flash($this->trans('invoice.create.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
        }

        return $this->render('CSBillInvoiceBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param  Request  $request
     * @param  Invoice  $invoice
     * @return Response
     */
    public function editAction(Request $request, Invoice $invoice)
    {
        $form = $this->createForm('invoice', $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveInvoice($invoice, 'send' === $action ? $action : null);

            $this->flash($this->trans('invoice.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
        }

        return $this->render(
            'CSBillInvoiceBundle:Default:edit.html.twig',
            array(
                'form' => $form->createView(),
                'invoice' => $invoice
            )
        );
    }

    /**
     * View a Invoice
     *
     * @param  Invoice  $invoice
     * @return Response
     */
    public function viewAction(Invoice $invoice)
    {
        $payments = $this->getRepository('CSBillPaymentBundle:Payment')->getPaymentsForInvoice($invoice);

        return $this->render(
            'CSBillInvoiceBundle:Default:view.html.twig',
            array(
                'invoice'  => $invoice,
                'payments' => $payments,
            )
        );
    }

    /**
     * @param Invoice $invoice
     * @param string  $action
     */
    private function saveInvoice(Invoice $invoice, $action = null)
    {
        $email = false;

        $statusRepository = $this->getRepository('CSBillInvoiceBundle:Status');

        switch ($action) {
            case 'send':
                $status = 'pending';
                $email = true;
                break;

            case 'draft':
                $status = 'draft';
                break;

            default:
                $status = null;
        }

        if (null !== $status) {
            /** @var \CSBill\InvoiceBundle\Entity\Status $invoiceStatus */
            $invoiceStatus = $statusRepository->findOneBy(array('name' => $status));
            $invoice->setStatus($invoiceStatus);
        }

        $this->save($invoice);

        if (true === $email) {
            $this->get('billing.mailer')->sendInvoice($invoice);
        }
    }
}
