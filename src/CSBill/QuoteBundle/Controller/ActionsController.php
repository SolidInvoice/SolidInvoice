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

use Symfony\Component\Validator\Constraints\Email;

use CS\CoreBundle\Controller\Controller;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Model\Status;

class ActionsController extends Controller
{
    public function acceptAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'accepted');

        // TODO : create invoice from quote

        $this->flash($this->trans('Quote Accepted'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function declineAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'declined');

        $this->flash($this->trans('Quote Declined'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function cancelAction(Quote $quote)
    {
        $this->setQuoteStatus($quote, 'cancelled');

        $this->flash($this->trans('Quote Cancelled'), 'success');

        return $this->redirect($this->generateUrl('_quotes_view', array('id' => $quote->getId())));
    }

    public function sendAction(Quote $quote)
    {
        $this->get('billing.mailer')->sendQuote($quote);

        if (strtolower($quote->getStatus()->getName()) === 'draft') {
            $this->setQuoteStatus($quote, 'pending');
        }

        $this->flash($this->trans('Quote Sent'), 'success');

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
