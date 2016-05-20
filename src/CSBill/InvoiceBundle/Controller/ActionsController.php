<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
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
     *
     * @throws InvalidTransitionException
     */
    public function transitionAction($action, Invoice $invoice)
    {
        if (!$this->get('finite.factory')->get($invoice, Graph::GRAPH)->can($action)) {
            throw new InvalidTransitionException($action);
        }

        $this->get('invoice.manager')->$action($invoice);

        $this->flash($this->trans('invoice.transition.action.'.$action), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', ['id' => $invoice->getId()]));
    }

    /**
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction(Invoice $invoice)
    {
        if ($invoice->getStatus() !== Graph::STATUS_PENDING) {
            $this->get('invoice.manager')->accept($invoice);
        } else {
            $this->get('billing.mailer')->sendInvoice($invoice);
        }

        $this->flash($this->trans('invoice.transition.action.sent'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', ['id' => $invoice->getId()]));
    }
}
