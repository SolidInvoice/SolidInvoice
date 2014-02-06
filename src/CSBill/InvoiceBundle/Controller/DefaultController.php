<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Entity\Invoice;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
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

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search) {
            if ($search) {
                $queryBuilder->andWhere('_client.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $viewAction = new RowAction($this->trans('View'), '_invoices_view');
        $viewAction->setAttributes(array('class' => 'btn'));

        $editAction = new RowAction($this->trans('Edit'), '_invoices_edit');
        $editAction->setAttributes(array('class' => 'btn'));

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deleted', 'users', 'paidDate', 'due', 'baseTotal', 'uuid'));

        $statusList = $this->getRepository('CSBillInvoiceBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillInvoiceBundle:Default:index.html.twig',
            array('status_list' => $statusList)
        );
    }

    /**
     * @param  Client   $client
     * @return Response
     */
    public function createAction(Client $client = null)
    {
        $request = $this->getRequest();

        $invoice = new Invoice;
        $invoice->setClient($client);

        $form = $this->createForm(new InvoiceType(), $invoice);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $this->saveInvoice($invoice);

                $this->flash($this->trans('Invoice created successfully'), 'success');

                return $this->redirect($this->generateUrl('_invoices_index'));
            }
        }

        return $this->render('CSBillInvoiceBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param  Invoice  $invoice
     * @return Response
     */
    public function editAction(Invoice $invoice)
    {
        $request = $this->getRequest();

        $form = $this->createForm(new InvoiceType(), $invoice);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $this->saveInvoice($invoice);

                $this->flash($this->trans('Invoice edited successfully'), 'success');

                return $this->redirect($this->generateUrl('_invoices_index'));
            }
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
        return $this->render('CSBillInvoiceBundle:Default:view.html.twig', array('invoice' => $invoice));
    }

    /**
     * @param Invoice $invoice
     * @param bool    $email
     */
    private function saveInvoice(Invoice $invoice, $email = false)
    {
        $em = $this->get('doctrine')->getManager();

        $statusRepository = $this->getRepository('CSBillInvoiceBundle:Status');

        switch ($this->getRequest()->request->get('save')) {
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
