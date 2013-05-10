<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\QuoteBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Entity\Quote;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Action\DeleteMassAction;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $source = new Entity('CSBillQuoteBundle:Quote');

        // Get a Grid instance
        $grid = $this->get('grid');

        // Attach the source to the grid
        $grid->setSource($source);

        // Custom actions column in the wanted position
        //$viewColumn = new ActionsColumn('info_column', $this->get('translator')->trans('Info'));
        //$grid->addColumn($viewColumn, 100);

        $viewAction = new RowAction($this->get('translator')->trans('View'), '_quotes_view');
        $viewAction->setAttributes(array('class' => 'btn'));
        //$viewAction->setColumn('info_column');
        //$grid->addRowAction($viewAction);

        //$editColumn = new ActionsColumn('edit_column', $this->get('translator')->trans('Edit'));
        //$grid->addColumn($editColumn, 200);

        $editAction = new RowAction($this->get('translator')->trans('Edit'), '_quotes_edit');
        $editAction->setAttributes(array('class' => 'btn'));
        //$editAction->setColumn('edit_column');
        //$grid->addRowAction($editAction);

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction));
        $grid->addColumn($actionsRow, 100);

        //$grid->addMassAction(new DeleteMassAction());

        $grid->hideColumns(array('updated', 'deleted', 'users', 'due', 'baseTotal'));

        $statusList = $this->getRepository('CSBillQuoteBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillQuoteBundle:Default:index.html.twig', array('status_list' => $statusList));
    }

    public function createAction()
    {
        $request = $this->getRequest();

        $quote = new Quote;

        $form = $this->createForm(new QuoteType(), $quote);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->get('doctrine')->getManager();

                $statusRepository = $this->getRepository('CSBillQuoteBundle:Status');

                if ($request->request->get('save') === 'draft') {
                    $status = $statusRepository->findOneByName('draft');
                } elseif ($request->request->get('save') === 'send') {
                    $status = $statusRepository->findOneByName('pending');
                }

                $quote->setStatus($status);

                $em->persist($quote);
                $em->flush();

                if ($request->request->get('save') === 'send') {
                    $this->get('billing.mailer')->sendQuote($quote);
                }

                $this->flash($this->trans('Quote created successfully'), 'success');

                return $this->redirect($this->generateUrl('_quotes_index'));
            }
        }

        return $this->render('CSBillQuoteBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Quote $quote)
    {
        $request = $this->getRequest();

        $form = $this->createForm(new QuoteType(), $quote);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $em = $this->get('doctrine')->getManager();

                $statusRepository = $this->getRepository('CSBillQuoteBundle:Status');

                if ($request->request->get('save') === 'draft') {
                    $status = $statusRepository->findOneByName('draft');
                } elseif ($request->request->get('save') === 'send') {
                    $status = $statusRepository->findOneByName('pending');
                }

                $quote->setStatus($status);

                $em->persist($quote);
                $em->flush();

                if ($request->request->get('save') === 'send') {
                    $this->get('billing.mailer')->sendQuote($quote);
                }

                $this->flash($this->trans('Quote edited successfully'), 'success');

                return $this->redirect($this->generateUrl('_quotes_index'));
            }
        }

        return $this->render('CSBillQuoteBundle:Default:edit.html.twig', array('form' => $form->createView()));
    }

    /**
     * View a Quote
     *
     * @param  Quote    $quote
     * @return Response
     */
    public function viewAction(Quote $quote)
    {
        return $this->render('CSBillQuoteBundle:Default:view.html.twig', array('quote' => $quote));
    }
}
