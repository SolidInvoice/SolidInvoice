<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\Controller;

use Symfony\Component\Validator\Constraints\Email;

use CS\CoreBundle\Controller\Controller;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Status;

class ActionsController extends Controller
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

    protected function setInvoiceStatus(Invoice $invoice, $status)
    {
        $status = $this->getRepository('CSBillInvoiceBundle:Status')->findOneByName($status);

        $invoice->setStatus($status);

        $em = $this->getEm();

        $em->persist($status);
        $em->flush();
    }
}
