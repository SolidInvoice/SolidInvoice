<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Model\Graph;

class ActionsController extends BaseController
{
    /**
     * @param string  $action
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function transitionAction($action, Invoice $invoice)
    {
        $this->get('invoice.manager')->$action($invoice);

        if (!$this->get('finite.factory')->get($invoice, Graph::GRAPH)->can($action)) {
            throw new InvalidTransitionException($action);
        }

        $this->flash($this->trans('invoice.action.transition.'.$action), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction(Invoice $invoice)
    {
        if ($invoice->getStatus() === Graph::STATUS_DRAFT) {
            $this->get('invoice.manager')->accept($invoice);
        } else {
            $this->get('billing.mailer')->sendInvoice($invoice);
        }

        $this->flash($this->trans('invoice.action.transition.sent'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }
}
