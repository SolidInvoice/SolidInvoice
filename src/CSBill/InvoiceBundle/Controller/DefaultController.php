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

use CS\CoreBundle\Controller\Controller;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Status;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Action\DeleteMassAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $source = new Entity('CSBillInvoiceBundle:Invoice');

        // Get a Grid instance
        $grid = $this->get('grid');

        /*$request = $this->getRequest();

        // TODO : get better way of adding filters & search instead of defining it in the controller like this
        $filters = new Filters($this->getRequest());

        $filters->add('all_clients', null, true, array('active_class' => 'label label-info', 'default_class' => 'label'));

        // TODO : get status from database
        $statuses = new Status;
        foreach ($statuses->getStatusList() as $status) {
            $filters->add($status.'_clients', function(QB $qb) use ($status) {
                $alias = $qb->getRootAlias();

                $qb->join($alias.'.status', 's')
                ->andWhere('s.name = :status_name')
                ->setParameter('status_name', $status);
            }, false, array('active_class' => 'label label-' . $statuses->getStatusLabel($status), 'default_class' => 'label'));
        }

        $search = $this->getRequest()->get('search');

        $source->manipulateQuery(function(QB $qb) use ($search, $filters) {

            if ($filters->isFilterActive()) {
                $filter = $filters->getActiveFilter();
                $filter($qb);
            }

            if ($search) {
                $alias = $qb->getRootAlias();

                $qb->andWhere($alias.'.name LIKE :search')
                ->setParameter('search', "%{$search}%");
            }
        });*/

        $statuses = new Status;

        // Attach the source to the grid
        $grid->setSource($source);

        $grid->getColumn('status.name')->manipulateRenderCell(function($value, Row $row, RouterInterface $router) use ($statuses) {
            return $statuses->getHtml($value);
        })->setSafe(false);

        // Custom actions column in the wanted position
        $viewColumn = new ActionsColumn('info_column', $this->get('translator')->trans('Info'));
        $grid->addColumn($viewColumn, 100);

        $viewAction = new RowAction($this->get('translator')->trans('View'), '_invoices_view');
        $viewAction->setColumn('info_column');
        $grid->addRowAction($viewAction);

        $editColumn = new ActionsColumn('edit_column', $this->get('translator')->trans('Edit'));
        $grid->addColumn($editColumn, 200);

        $editAction = new RowAction($this->get('translator')->trans('Edit'), '_invoices_edit');
        $editAction->setColumn('edit_column');
        $grid->addRowAction($editAction);

        $grid->addMassAction(new DeleteMassAction());

        $grid->hideColumns(array('updated', 'deleted'));

        $statusList = $this->getDoctrine()->getManager()->getRepository('CSBillInvoiceBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillInvoiceBundle:Default:index.html.twig', array('status_list' => $statusList));
    }

    public function createAction()
    {
        $request = $this->getRequest();

        $invoice = new Invoice;

        $form = $this->createForm(new InvoiceType(), $invoice);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->get('doctrine')->getManager();

                $statusRepository = $this->getRepository('CSBillInvoiceBundle:Status');

                if ($request->request->get('save') === 'draft') {
                    $status = $statusRepository->findOneByName('draft');
                } elseif ($request->request->get('save') === 'send') {
                    $status = $statusRepository->findOneByName('pending');
                }

                $invoice->setStatus($status);

                $em->persist($invoice);
                $em->flush();

                if ($request->request->get('save') === 'send') {
                    $this->get('billing.email')->sendInvoice($invoice);
                }

                $this->flash($this->trans('Invoice created successfully'), 'success');

                return $this->redirect($this->generateUrl('_invoices_index'));
            }
        }

        return $this->render('CSBillInvoiceBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Invoice $invoice)
    {
        $request = $this->getRequest();

        $form = $this->createForm(new InvoiceType(), $invoice);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->get('doctrine')->getManager();

                $statusRepository = $this->getRepository('CSBillInvoiceBundle:Status');

                if ($request->request->get('save') === 'draft') {
                    $status = $statusRepository->findOneByName('draft');
                } elseif ($request->request->get('save') === 'send') {
                    $status = $statusRepository->findOneByName('pending');
                }

                $invoice->setStatus($status);

                $em->persist($invoice);
                $em->flush();

                if ($request->request->get('save') === 'send') {
                    $this->get('billing.email')->sendInvoice($invoice);
                }

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
}
