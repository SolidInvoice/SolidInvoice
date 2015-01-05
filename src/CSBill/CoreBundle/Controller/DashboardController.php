<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Controller;

use CSBill\ClientBundle\Entity\Status as ClientStatus;
use CSBill\QuoteBundle\Entity\Status as QuoteStatus;

class DashboardController extends BaseController
{
    /**
     * Homepage action
     */
    public function indexAction()
    {
        /** @var \CSBill\PaymentBundle\Repository\PaymentRepository $paymentRepository */
        $paymentRepository = $this->getRepository('CSBillPaymentBundle:Payment');

        return $this->render(
            'CSBillCoreBundle:Dashboard:index.html.twig',
            array(
                'totalClients' => $this->getRepository('CSBillClientBundle:Client')->getTotalClients(ClientStatus::STATUS_ACTIVE),
                'totalQuotes' => $this->getRepository('CSBillQuoteBundle:Quote')->getTotalQuotes(QuoteStatus::STATUS_DECLINED),
                'totalInvoices' => $this->getRepository('CSBillInvoiceBundle:Invoice')->getCountByStatus('pending'),
                'totalIncome' => $paymentRepository->getTotalIncome()[1]
            )
        );
    }
}
