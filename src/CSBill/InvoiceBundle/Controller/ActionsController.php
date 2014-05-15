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

class ActionsController extends BaseController
{
    /**
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cancelAction(Invoice $invoice)
    {
        $this->setInvoiceStatus($invoice, 'cancelled');

        $this->flash($this->trans('Invoice Cancelled'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function payAction(Invoice $invoice)
    {
        $this->get('invoice.manager')->markPaid($invoice);

        $this->flash($this->trans('Invoice Paid'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction(Invoice $invoice)
    {
        $this->get('billing.mailer')->sendInvoice($invoice);

        if (strtolower($invoice->getStatus()->getName()) === 'draft') {
            $this->setInvoiceStatus($invoice, 'pending');
        }

        $this->flash($this->trans('Invoice Sent'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    /**
     * @param Invoice $invoice
     * @param string  $status
     */
    protected function setInvoiceStatus(Invoice $invoice, $status)
    {
        $status = $this->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);

        $invoice->setStatus($status);

        $entityManager = $this->getEm();

        $entityManager->persist($invoice);
        $entityManager->flush();
    }
}
