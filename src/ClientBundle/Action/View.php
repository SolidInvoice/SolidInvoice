<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final class View
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    /**
     * @return array{client: Client, payments: array<string, mixed>, total_invoices_pending: int, total_invoices_paid: int, total_income: mixed, total_outstanding: int}
     */
    #[Template('@SolidInvoiceClient/Default/view.html.twig')]
    public function __invoke(Client $client): array
    {
        return [
            'client' => $client,
            'payments' => $this->paymentRepository->getPaymentsForClient($client),
            'total_invoices_pending' => $this->invoiceRepository->getCountByStatus(InvoiceStatus::Pending, $client),
            'total_invoices_paid' => $this->invoiceRepository->getCountByStatus(InvoiceStatus::Paid, $client),
            'total_income' => $this->paymentRepository->getTotalIncomeForClient($client),
            'total_outstanding' => $this->invoiceRepository->getTotalOutstanding($client),
        ];
    }
}
