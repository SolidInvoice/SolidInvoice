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

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ActionsController extends BaseController
{
    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function acceptAction(Quote $quote): RedirectResponse
    {
        $invoice = $this->get('quote.manager')->accept($quote);

        $this->flash($this->trans('quote.accepted'), 'success');

        return $this->redirectToRoute('_invoices_view', ['id' => $invoice->getId()]);
    }

    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function declineAction(Quote $quote): RedirectResponse
    {
        $this->get('quote.manager')->decline($quote);

        $this->flash($this->trans('quote.declined'), 'success');

        return $this->redirectToRoute('_quotes_view', ['id' => $quote->getId()]);
    }

    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function cancelAction(Quote $quote): RedirectResponse
    {
        $this->get('quote.manager')->cancel($quote);

        $this->flash($this->trans('quote.cancelled'), 'success');

        return $this->redirectToRoute('_quotes_view', ['id' => $quote->getId()]);
    }

    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function reopenAction(Quote $quote): RedirectResponse
    {
        $this->get('quote.manager')->reopen($quote);

        $this->flash($this->trans('quote.reopened'), 'success');

        return $this->redirectToRoute('_quotes_view', ['id' => $quote->getId()]);
    }

    /**
     * @param Quote $quote
     *
     * @return RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function sendAction(Quote $quote): RedirectResponse
    {
        $this->get('quote.manager')->send($quote);

        $this->flash($this->trans('quote.sent'), 'success');

        return $this->redirectToRoute('_quotes_view', ['id' => $quote->getId()]);
    }
}
