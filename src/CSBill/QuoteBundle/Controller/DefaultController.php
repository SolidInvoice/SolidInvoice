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
use Doctrine\ORM\QueryBuilder;
use CSBill\ClientBundle\Entity\Client;

class DefaultController extends BaseController
{
    /**
     * List all the available quotes
     *
     * @param  Request  $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('CSBillQuoteBundle:Quote');

        // Get a Grid instance
        $grid = $this->get('grid');
        $translator = $this->get('translator');

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search) {
            if ($search) {
                $queryBuilder->andWhere('_client.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $viewAction = new RowAction('<i class="icon-eye-open"></i>', '_quotes_view');
        $viewAction->addAttribute('title', $translator->trans('quote_view'));
        $viewAction->addAttribute('rel', 'tooltip');

        $editAction = new RowAction('<i class="icon-edit"></i>', '_quotes_view');
        $editAction->addAttribute('title', $translator->trans('quote_edit'));
        $editAction->addAttribute('rel', 'tooltip');

        $deleteAction = new RowAction('<i class="icon-remove"></i>', '_quotes_delete');
        $deleteAction->setAttributes(
            array(
                'title' => $translator->trans('quote_delete'),
                'rel' => 'tooltip',
                'data-confirm' => $translator->trans('confirm_delete'),
                'class' => 'delete-client',
            )
        );

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction, $deleteAction));
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
     * @param  Request  $request
     * @param  Client   $client
     * @return Response
     */
    public function createAction(Request $request, Client $client = null)
    {
        $clients = $this->getRepository('CSBillClientBundle:Client');

        if (!$clients->getTotalClients() > 0) {
            return $this->render('CSBillQuoteBundle:Default:empty_clients.html.twig');
        }

        $quote = new Quote;
        $quote->setClient($client);

        $form = $this->createForm(new QuoteType(), $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, $action);

            $this->flash($this->trans('quote_creat_sucess'), 'success');

            return $this->redirect($this->generateUrl('_quotes_index'));
        }

        return $this->render('CSBillQuoteBundle:Default:create.html.twig', array('form' => $form->createView()));
    }

    /**
     * Edit a quote
     *
     * @param  Request  $request
     * @param  Quote    $quote
     * @return Response
     */
    public function editAction(Request $request, Quote $quote)
    {
        $form = $this->createForm(new QuoteType(), $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $action = $request->request->get('save');
            $this->saveQuote($quote, $action);

            $this->flash($this->trans('quote_edit_success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_index'));
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
     * @param Quote  $quote
     * @param string $action
     */
    private function saveQuote(Quote $quote, $action)
    {
        $email = false;

        $em = $this->getEm();

        $statusRepository = $this->getRepository('CSBillQuoteBundle:Status');

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
