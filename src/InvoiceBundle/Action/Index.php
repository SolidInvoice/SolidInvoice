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

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;

final class Index
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PaymentRepository $paymentRepository
    ) {
    }

    public function __invoke(Request $request)
    {
        return new Template(
            '@SolidInvoiceInvoice/Default/index.html.twig',
            [
                'recurring' => false,
                'status_list_count' => [
                    Graph::STATUS_PENDING => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PENDING),
                    Graph::STATUS_PAID => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PAID),
                    Graph::STATUS_CANCELLED => $this->invoiceRepository->getCountByStatus(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $this->invoiceRepository->getCountByStatus(Graph::STATUS_DRAFT),
                    Graph::STATUS_OVERDUE => $this->invoiceRepository->getCountByStatus(Graph::STATUS_OVERDUE),
                ],
                'total_income' => $this->paymentRepository->getTotalIncome(),
                'total_outstanding' => $this->invoiceRepository->getTotalOutstanding(),
            ]
        );
    }
}
