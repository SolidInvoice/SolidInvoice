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

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Entity\Invoice;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use CSBill\ClientBundle\Entity\Client;

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

        $viewIcon = $templating->render('{{ icon("eye") }}');
        $viewAction = new RowAction($viewIcon, '_invoices_view');
        $viewAction->addAttribute('title', $translator->trans('invoice_view'));
        $viewAction->addAttribute('rel', 'tooltip');

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_invoices_edit');
        $editAction->addAttribute('title', $translator->trans('invoice_edit'));
        $editAction->addAttribute('rel', 'tooltip');

        $payIcon = $templating->render('{{ icon("credit-card") }}');
        $payAction = new RowAction($payIcon, '_payments_create');
        
        $payAction->addAttribute('title', $translator->trans('pay_now'));
        $payAction->addAttribute('rel', 'tooltip');

        $payAction->manipulateRender(function (RowAction $rowAction, Row $row) {
            if ('pending' !== $row->getField('status.name')) {
                $rowAction->setTitle('');;
            }

            return $rowAction;
        });

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction, $payAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deleted', 'users', 'paidDate', 'due', 'baseTotal', 'uuid'));

        $grid->getColumn('total')->setCurrencyCode($this->container->getParameter('currency'));
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
            'client.name' => array('operator' => 'isNotNull')
        ));

        $statusList = $this->getRepository('CSBillInvoiceBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'CSBillInvoiceBundle:Default:index.html.twig',
            array(
                'status_list' => $statusList
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
        $clients = $this->getRepository('CSBillClientBundle:Client');

        if (!$clients->getTotalClients() > 0) {
            return $this->render('CSBillInvoiceBundle:Default:empty_clients.html.twig');
        }

        $invoice = new Invoice;
        $invoice->setClient($client);

        $form = $this->createForm(new InvoiceType(), $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $action = $request->request->get('save');
            $this->saveInvoice($invoice, $action);

            $this->flash($this->trans('Invoice created successfully'), 'success');

            return $this->redirect($this->generateUrl('_invoices_index'));
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
        $form = $this->createForm(new InvoiceType(), $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $action = $request->request->get('save');
            $this->saveInvoice($invoice, $action);

            $this->flash($this->trans('Invoice edited successfully'), 'success');

            return $this->redirect($this->generateUrl('_invoices_index'));
        }

        return $this->render('CSBillInvoiceBundle:Default:edit.html.twig', array('form' => $form->createView()));
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
                'payments' => $payments
            )
        );
    }

    /**
     * @param Invoice $invoice
     * @param string  $action
     */
    private function saveInvoice(Invoice $invoice, $action)
    {
        $email = false;
        $em = $this->get('doctrine')->getManager();

        $statusRepository = $this->getRepository('CSBillInvoiceBundle:Status');

        switch ($action) {
            case 'send':
                $status = 'pending';
                $email = true;
                break;

            case 'draft':
            default:
                $status = 'draft';
                break;
        }

        /** @var \CSBill\InvoiceBundle\Entity\Status $invoiceStatus */
        $invoiceStatus = $statusRepository->findOneBy(array('name' => $status));

        $invoice->setStatus($invoiceStatus);

        $em->persist($invoice);
        $em->flush();

        if (true === $email) {
            $this->get('billing.mailer')->sendInvoice($invoice);
        }
    }
}
