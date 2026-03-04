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

namespace SolidInvoice\PaymentBundle\Search;

use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\Routing\RouterInterface;

final class PaymentResultFormatter implements ResultFormatterInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function getIndexName(): string
    {
        return 'payments';
    }

    public function format(array $hit): SearchResult
    {
        $title = $hit['reference'] ?? $hit['invoiceRef'] ?? $hit['id'];
        $subtitle = $hit['clientName'] ?? '';
        if (isset($hit['invoiceRef']) && $hit['invoiceRef'] !== '') {
            $subtitle = sprintf('%s — %s', $hit['clientName'] ?? '', $hit['invoiceRef']);
        }

        $invoiceId = $hit['invoiceId'] ?? null;
        $url = $invoiceId !== null
            ? $this->router->generate('_invoices_view', ['id' => $invoiceId])
            : $this->router->generate('_payments_index');

        return new SearchResult(
            type: 'payment',
            id: $hit['id'],
            title: $title,
            subtitle: $subtitle,
            icon: 'credit-card',
            url: $url,
            status: $hit['status'] ?? null,
        );
    }
}
