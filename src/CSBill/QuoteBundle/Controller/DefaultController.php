<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\DataGridBundle\Grid\GridCollection;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * List all the available quotes.
     *
     * @return Response
     */
    public function indexAction()
    {
        $gridCollection = new GridCollection();
        $gridCollection->add('csbill.quote.grid.default_grid', 'active', 'check');
        $gridCollection->add('csbill.quote.grid.archived_grid', 'archived', 'archive');

        $grid = $this->get('grid')->create($gridCollection);

        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->getRepository('CSBillQuoteBundle:Quote');

        // Return the response of the grid to the template
        return $grid->getGridResponse(
            array(
                'status_list' => array(
                    Graph::STATUS_PENDING,
                    Graph::STATUS_ACCEPTED,
                    Graph::STATUS_CANCELLED,
                    Graph::STATUS_DRAFT,
                    Graph::STATUS_DECLINED,
                ),
                'status_list_count' => array(
                    Graph::STATUS_PENDING => $quoteRepository->getTotalQuotes(Graph::STATUS_PENDING),
                    Graph::STATUS_ACCEPTED => $quoteRepository->getTotalQuotes(Graph::STATUS_ACCEPTED),
                    Graph::STATUS_CANCELLED => $quoteRepository->getTotalQuotes(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $quoteRepository->getTotalQuotes(Graph::STATUS_DRAFT),
                    Graph::STATUS_DECLINED => $quoteRepository->getTotalQuotes(Graph::STATUS_DECLINED),
                ),
            )
        );
    }

    /**
     * Create a new Quote.
     *
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
            return $this->render('CSBillQuoteBundle:Default:empty_clients.html.twig');
        }

        $quote = new Quote();
        $quote->setClient($client);

        $form = $this->createForm('quote', $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, $action);

            $this->flash($this->trans('quote.action.create.success'), 'success');

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
     * Edit a quote.
     *
     * @param Request $request
     * @param Quote   $quote
     *
     * @return Response
     */
    public function editAction(Request $request, Quote $quote)
    {
        $form = $this->createForm('quote', $quote);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, 'send' === $action ? $action : null);

            $this->flash($this->trans('quote.action.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
        }

        return $this->render(
            'CSBillQuoteBundle:Default:edit.html.twig',
            array(
                'form' => $form->createView(),
                'quote' => $quote,
            )
        );
    }

    /**
     * View a Quote.
     *
     * @param Quote $quote
     *
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
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);
        $dispatcher = $this->get('event_dispatcher');

        if (!$quote->getId()) {
            $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_CREATE, new QuoteEvent($quote));
        }

        if ($action === Graph::STATUS_PENDING) {
            $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_SEND, new QuoteEvent($quote));
            $finite->apply(Graph::TRANSITION_SEND);
            $dispatcher->dispatch(QuoteEvents::QUOTE_POST_SEND, new QuoteEvent($quote));
        } else {
            $finite->apply(Graph::TRANSITION_NEW);
        }

        if (!$quote->getId()) {
            $dispatcher->dispatch(QuoteEvents::QUOTE_POST_CREATE, new QuoteEvent($quote));
        }

        $this->save($quote);
    }
}
