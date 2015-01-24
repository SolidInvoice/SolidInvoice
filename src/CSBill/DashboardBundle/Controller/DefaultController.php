<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Controller;

use CSBill\ClientBundle\Entity\Status as ClientStatus;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\QuoteBundle\Entity\Status as QuoteStatus;

class DefaultController extends BaseController
{
    /**
     * Homepage action
     */
    public function indexAction()
    {
        return $this->render(
            'CSBillDashboardBundle:Default:index.html.twig',
            array(
                'totalClients' => $this->getRepository('CSBillClientBundle:Client')->getTotalClients(ClientStatus::STATUS_ACTIVE),
                'totalQuotes' => $this->getRepository('CSBillQuoteBundle:Quote')->getTotalQuotes(QuoteStatus::STATUS_DECLINED),
                'totalInvoices' => $this->getRepository('CSBillInvoiceBundle:Invoice')->getCountByStatus(Graph::STATUS_PENDING),
                'totalIncome' => $this->getRepository('CSBillPaymentBundle:Payment')->getTotalIncome(),
            )
        );
    }
}
