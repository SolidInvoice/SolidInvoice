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

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Entity\Quote;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use CSBill\ClientBundle\Entity\Client;

class DefaultController extends BaseController
{
    /**
     * List all the available quotes
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('CSBillQuoteBundle:Quote');

        // Get a Grid instance
        $grid = $this->get('grid');

        $search = $request->get('search');

        $source->manipulateQuery(function ($queryBuilder) use ($search) {
            if ($search) {
                $queryBuilder->andWhere('_client.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $viewAction = new RowAction($this->get('translator')->trans('View'), '_quotes_view');
        $viewAction->setAttributes(array('class' => 'btn'));

        $editAction = new RowAction($this->get('translator')->trans('Edit'), '_quotes_edit');
        $editAction->setAttributes(array('class' => 'btn'));

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deleted', 'users', 'due', 'baseTotal', 'uuid'));

        $grid->setPermanentFilters(array(
            'client.name' => array('operator' => 'isNotNull')
        ));

        $statusList = $this->getRepository('CSBillQuoteBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'CSBillQuoteBundle:Default:index.html.twig',
            array(
                'status_list' => $statusList
            )
        );
    }

    /**
     * Create a new Quote
     *
     * @param  Client   $client
     * @return Response
     */
    public function createAction(Client $client = null)
    {
        $request = $this->getRequest();

        $quote = new Quote;
        $quote->setClient($client);

        $form = $this->createForm(new QuoteType(), $quote);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $this->saveQuote($quote);

                $this->flash($this->trans('Quote created successfully'), 'success');

                return $this->redirect($this->generateUrl('_quotes_index'));
            }
        }

        return $this->render('CSBillQuoteBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    /**
     * Edit a quote
     *
     * @param  Quote    $quote
     * @return Response
     */
    public function editAction(Quote $quote)
    {
        $request = $this->getRequest();

        $form = $this->createForm(new QuoteType(), $quote);

        if ($request->getMethod() === 'POST') {

            $form->bind($request);

            if ($form->isValid()) {

                $this->saveQuote($quote);

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

    /**
     * @param Quote $quote
     * @param bool  $email
     */
    private function saveQuote(Quote $quote, $email = false)
    {
        $em = $this->get('doctrine')->getManager();

        $statusRepository = $this->getRepository('CSBillQuoteBundle:Status');

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

        /** @var \CSBill\QuoteBundle\Entity\Status $quoteStatus */
        $quoteStatus = $statusRepository->findOneBy(array('name' => $status));

        $quote->setStatus($quoteStatus);

        $em->persist($quote);
        $em->flush();

        if (true === $email) {
            $this->get('billing.mailer')->sendQuote($quote);
        }
    }
}
