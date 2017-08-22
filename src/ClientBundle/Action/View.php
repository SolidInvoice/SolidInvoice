<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;

final class View
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
            '@SolidInvoiceClient/Default/view.html.twig',
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
