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
    public function cancelAction(Invoice $invoice)
    {
        $this->setInvoiceStatus($invoice, 'cancelled');

        $this->flash($this->trans('Invoice Cancelled'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

    public function payAction(Invoice $invoice)
    {
        $this->setInvoiceStatus($invoice, 'paid');
        $invoice->setPaidDate(new \DateTime('NOW'));

        $this->flash($this->trans('Invoice Paid'), 'success');

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }

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
     * @param string $status
     */
    protected function setInvoiceStatus(Invoice $invoice, $status)
    {
        $status = $this->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);

        $invoice->setStatus($status);

        $em = $this->getEm();

        $em->persist($status);
        $em->flush();
    }
}
