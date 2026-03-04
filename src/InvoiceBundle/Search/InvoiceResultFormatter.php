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

namespace SolidInvoice\InvoiceBundle\Search;

use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\Routing\RouterInterface;

final class InvoiceResultFormatter implements ResultFormatterInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function getIndexName(): string
    {
        return 'invoices';
    }

    public function format(array $hit): SearchResult
    {
        return new SearchResult(
            type: 'invoice',
            id: $hit['id'],
            title: $hit['invoiceId'] ?? $hit['id'],
            subtitle: $hit['clientName'] ?? '',
            url: $this->router->generate('_invoices_view', ['id' => $hit['id']]),
            status: $hit['status'] ?? null,
            meta: isset($hit['total']) ? number_format((float) $hit['total'] / 100, 2) : null,
        );
    }
}
