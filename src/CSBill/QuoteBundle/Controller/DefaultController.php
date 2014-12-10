<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $viewAction = new RowAction($viewIcon, '_quotes_view');
        $viewAction->addAttribute('title', $translator->trans('quote.view'));
        $viewAction->addAttribute('rel', 'tooltip');

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_quotes_edit');
        $editAction->addAttribute('title', $translator->trans('quote.edit'));
        $editAction->addAttribute('rel', 'tooltip');

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deletedAt', 'users', 'due', 'baseTotal', 'uuid'));

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

        $statusList = $this->getRepository('CSBillQuoteBundle:Status')->findAll();

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            'CSBillQuoteBundle:Default:index.html.twig',
            array(
                'status_list' => $statusList,
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
        /** @var \CSBill\ClientBundle\Repository\ClientRepository $clients */
        $clients = $this->getRepository('CSBillClientBundle:Client');

        if (!$clients->getTotalClients() > 0) {
            return $this->render('CSBillQuoteBundle:Default:empty_clients.html.twig');
        }

        $quote = new Quote();
        $quote->setClient($client);

        $form = $this->createForm('quote', $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, $action);

            $this->flash($this->trans('quote.create.success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
        }

        return $this->render(
            'CSBillQuoteBundle:Default:create.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
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
        $form = $this->createForm('quote', $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, 'send' === $action ? $action : null);

            $this->flash($this->trans('quote.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
        }

        return $this->render(
            'CSBillQuoteBundle:Default:edit.html.twig',
            array(
                'form' => $form->createView(),
                'quote' => $quote
            )
        );
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
    private function saveQuote(Quote $quote, $action = null)
    {
        $email = false;

        $statusRepository = $this->getRepository('CSBillQuoteBundle:Status');

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
            /** @var \CSBill\QuoteBundle\Entity\Status $quoteStatus */
            $quoteStatus = $statusRepository->findOneBy(array('name' => $status));

            $quote->setStatus($quoteStatus);
        }

        $this->save($quote);

        if (true === $email) {
            $this->get('billing.mailer')->sendQuote($quote);
        }
    }
}
