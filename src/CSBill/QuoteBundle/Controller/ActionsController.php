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

class ActionsController extends BaseController
{
    public function acceptAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'accepted');

        $invoice = $this->get('invoice.manager')->createFromQuote($quote);

        $em = $this->getEm();

        $em->persist($invoice);
        $em->flush();

        $this->get('billing.mailer')->sendInvoice($invoice);

        // TODO : we should be able to specify if the new invoice must be emailed or not
        // TODO : we should set a default due date for invoices

        $this->flash($this->trans('quote_accepted'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function declineAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'declined');

        $this->flash($this->trans('quote_declined'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function cancelAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'cancelled');

        $this->flash($this->trans('quote_cancelled'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function sendAction(Quote $quote)
    {
        $this->get('billing.mailer')->sendQuote($quote);

        if (strtolower($quote->getStatus()->getName()) === 'draft') {
            $this->setQuoteStatus($quote, 'pending');
        }

        $this->flash($this->trans('quote_sent'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    protected function setQuoteStatus(Quote $quote, $status)
    {
        $status = $this->getRepository('CSBillQuoteBundle:Status')->findOneByName($status);

        $quote->setStatus($status);

        $em = $this->getEm();

        $em->persist($status);
        $em->flush();
    }
}
