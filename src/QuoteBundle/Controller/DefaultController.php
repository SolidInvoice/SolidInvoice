<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Money\Currency;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * List all the available quotes.
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->getRepository('CSBillQuoteBundle:Quote');

        // Return the response of the grid to the template
        return $this->render(
            'CSBillQuoteBundle:Default:index.html.twig',
            [
                'status_list_count' => [
                    Graph::STATUS_PENDING => $quoteRepository->getTotalQuotes(Graph::STATUS_PENDING),
                    Graph::STATUS_ACCEPTED => $quoteRepository->getTotalQuotes(Graph::STATUS_ACCEPTED),
                    Graph::STATUS_CANCELLED => $quoteRepository->getTotalQuotes(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $quoteRepository->getTotalQuotes(Graph::STATUS_DRAFT),
                    Graph::STATUS_DECLINED => $quoteRepository->getTotalQuotes(Graph::STATUS_DECLINED),
                ],
            ]
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
    public function createAction(Request $request, Client $client = null): Response
    {
        /** @var \CSBill\ClientBundle\Repository\ClientRepository $clients */
        $clients = $this->getRepository('CSBillClientBundle:Client');

        if (!$clients->getTotalClients() > 0) {
            return $this->render('CSBillQuoteBundle:Default:empty_clients.html.twig');
        }

        $quote = new Quote();
        $quote->setClient($client);

        $options = [];

        if ($client && $client->getCurrency()) {
            $options['currency'] = $client->getCurrency();
        }

        $form = $this->createForm(QuoteType::class, $quote, $options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, $action);

            $this->get('event_dispatcher')
                ->dispatch(QuoteEvents::QUOTE_POST_CREATE, new QuoteEvent($quote));

            $this->flash($this->trans('quote.action.create.success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_view', ['id' => $quote->getId()]));
        }

        return $this->render(
            'CSBillQuoteBundle:Default:create.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getFieldsAction(Request $request)
    {
        $form = $this->createForm(QuoteType::class, null, ['currency' => new Currency($request->get('currency'))]);

        return $this->json($this->get('csbill_core.field.renderer')->render($form->createView(), 'children[items].vars[prototype]'));
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
            $this->save($quote);
            $dispatcher->dispatch(QuoteEvents::QUOTE_POST_SEND, new QuoteEvent($quote));
        } else {
            if (!$quote->getId()) {
                $finite->apply(Graph::TRANSITION_NEW);
            }

            $this->save($quote);
        }
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
        $currency = $quote->getClient()->getCurrency() ?: new Currency($this->getParameter('currency'));

        $form = $this->createForm(QuoteType::class, $quote, ['currency' => $currency]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $action = $request->request->get('save');
            $this->saveQuote($quote, 'send' === $action ? $action : null);

            $this->flash($this->trans('quote.action.edit.success'), 'success');

            return $this->redirect($this->generateUrl('_quotes_view', ['id' => $quote->getId()]));
        }

        return $this->render(
            'CSBillQuoteBundle:Default:edit.html.twig',
            [
                'form' => $form->createView(),
                'quote' => $quote,
            ]
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
        return $this->render('CSBillQuoteBundle:Default:view.html.twig', ['quote' => $quote]);
    }

    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     */
    public function cloneAction(Quote $quote)
    {
        $newQuote = $this->get('quote.manager')->duplicate($quote);

        $this->flash($this->trans('quote.clone.success'), 'success');

        return $this->redirectToRoute('_quotes_view', ['id' => $newQuote->getId()]);
    }
}
