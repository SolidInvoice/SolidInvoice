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

class DashboardController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recentAction()
    {
        $invoices = $this->getRepository('CSBillInvoiceBundle:Invoice')->getRecentInvoices();

        return $this->render('CSBillInvoiceBundle:Dashboard:recent.html.twig', array('invoices' => $invoices));
    }
} 