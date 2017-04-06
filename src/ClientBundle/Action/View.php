<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Action;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Templating\Template;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use CSBill\PaymentBundle\Repository\PaymentRepository;

class View
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @param PaymentRepository $paymentRepository
     * @param InvoiceRepository $invoiceRepository
     */
    public function __construct(PaymentRepository $paymentRepository, InvoiceRepository $invoiceRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @param Client $client
     *
     * @return Template
     */
    public function __invoke(Client $client): Template
    {
        return new Template(
            '@CSBillClient/Default/view.html.twig',
            [
                'client' => $client,
                'payments' => $this->paymentRepository->getPaymentsForClient($client),
                'total_invoices_pending' => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PENDING, $client),
                'total_invoices_paid' => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PAID, $client),
                'total_income' => $this->paymentRepository->getTotalIncome($client),
                'total_outstanding' => $this->invoiceRepository->getTotalOutstanding($client),
            ]
        );
    }
}