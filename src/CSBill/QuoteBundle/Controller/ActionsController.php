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
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Entity\Status;

class ActionsController extends BaseController
{
    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function acceptAction(Quote $quote)
    {
        // @TODO: APply transition for quote
        //$this->setQuoteStatus($quote, Status::STATUS_ACCEPTED);

        $invoice = $this->get('invoice.manager')->createFromQuote($quote);

        $this->flash($this->trans('quote.accepted'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function declineAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, Status::STATUS_DECLINED);

        $this->flash($this->trans('quote.declined'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cancelAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, Status::STATUS_CANCELLED);

        $this->flash($this->trans('quote.cancelled'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction(Quote $quote)
    {
        $this->get('billing.mailer')->sendQuote($quote);

        if (strtolower($quote->getStatus()->getName()) === Status::STATUS_DRAFT) {
            $this->setQuoteStatus($quote, Status::STATUS_PENDING);
        }

        $this->flash($this->trans('quote.sent'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    /**
     * @param string $status
     */
    protected function setQuoteStatus(Quote $quote, $status)
    {
        $status = $this->getRepository('CSBillQuoteBundle:Status')->findOneByName($status);

        $quote->setStatus($status);

        $em = $this->getEm();

        $em->persist($status);
        $em->flush();
    }
}
