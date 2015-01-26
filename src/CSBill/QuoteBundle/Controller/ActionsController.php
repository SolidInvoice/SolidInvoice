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

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Model\Graph;

class ActionsController extends BaseController
{
    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function acceptAction(Quote $quote)
    {
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);

        if (!$finite->can(Graph::TRANSITION_ACCEPT)) {
            throw new InvalidTransitionException(Graph::TRANSITION_ACCEPT);
        }

        $dispatcher = $this->get('event_dispatcher');

        $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_ACCEPT, new QuoteEvent($quote));

        $invoice = $this->get('invoice.manager')->createFromQuote($quote);

        $finite->apply(Graph::TRANSITION_ACCEPT);

        $dispatcher->dispatch(QuoteEvents::QUOTE_POST_ACCEPT, new QuoteEvent($quote));

        $this->save($quote);

        $this->flash($this->trans('quote.accepted'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function declineAction(Quote $quote)
    {
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);

        if (!$finite->can(Graph::TRANSITION_DECLINE)) {
            throw new InvalidTransitionException(Graph::TRANSITION_DECLINE);
        }

        $dispatcher = $this->get('event_dispatcher');

        $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_DECLINE, new QuoteEvent($quote));

        $finite->apply(Graph::TRANSITION_DECLINE);

        $dispatcher->dispatch(QuoteEvents::QUOTE_POST_DECLINE, new QuoteEvent($quote));

        $this->save($quote);

        $this->flash($this->trans('quote.declined'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function cancelAction(Quote $quote)
    {
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);

        if (!$finite->can(Graph::TRANSITION_CANCEL)) {
            throw new InvalidTransitionException(Graph::TRANSITION_CANCEL);
        }

        $dispatcher = $this->get('event_dispatcher');

        $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_CANCEL, new QuoteEvent($quote));

        $finite->apply(Graph::TRANSITION_CANCEL);

        $dispatcher->dispatch(QuoteEvents::QUOTE_POST_CANCEL, new QuoteEvent($quote));

        $this->save($quote);

        $this->flash($this->trans('quote.cancelled'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function reopenAction(Quote $quote)
    {
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);

        if (!$finite->can(Graph::TRANSITION_REOPEN)) {
            throw new InvalidTransitionException(Graph::TRANSITION_REOPEN);
        }
        $finite->apply(Graph::TRANSITION_REOPEN);

        $this->save($quote);

        $this->flash($this->trans('quote.reopened'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function sendAction(Quote $quote)
    {
        $finite = $this->get('finite.factory')->get($quote, Graph::GRAPH);

        if ($quote->getStatus() !== Graph::STATUS_PENDING) {
            if (!$finite->can(Graph::TRANSITION_SEND)) {
                throw new InvalidTransitionException(Graph::TRANSITION_SEND);
            }

            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(QuoteEvents::QUOTE_PRE_SEND, new QuoteEvent($quote));
            $finite->apply(Graph::TRANSITION_SEND);
            $dispatcher->dispatch(QuoteEvents::QUOTE_POST_SEND, new QuoteEvent($quote));
        } else {
            $this->get('billing.mailer')->sendQuote($quote);
        }

        $this->save($quote);

        $this->flash($this->trans('quote.sent'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }
}
