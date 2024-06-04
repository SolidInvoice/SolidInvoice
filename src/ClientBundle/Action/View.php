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
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class View
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function __invoke(string $id): Template
    {
        $client = $this->clientRepository->find($id);

        if (! $client instanceof Client) {
            throw new NotFoundHttpException();
        }

        return new Template(
            '@SolidInvoiceClient/Default/view.html.twig',
            [
                'client' => $client,
                'payments' => $this->paymentRepository->getPaymentsForClient($client),
                'total_invoices_pending' => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PENDING, $client),
                'total_invoices_paid' => $this->invoiceRepository->getCountByStatus(Graph::STATUS_PAID, $client),
                'total_income' => $this->paymentRepository->getTotalIncomeForClient($client),
                'total_outstanding' => $this->invoiceRepository->getTotalOutstanding($client),
            ]
        );
    }
}
