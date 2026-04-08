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

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class PaymentResultFormatter implements QualifiedResultFormatterInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function getSupportedQualifiers(): array
    {
        return [
            'status' => 'status',
            'client' => 'client.name',
            'amount' => 'total',
        ];
    }

    public function getIndexName(): string
    {
        return 'payments';
    }

    public function format(array $hit): SearchResult
    {
        $reference = isset($hit['reference']) && $hit['reference'] !== '' ? $hit['reference'] : null;
        $invoiceRef = $hit['invoice']['invoiceId'] ?? null;
        $title = $reference ?? $invoiceRef ?? $hit['id'];

        $clientName = $hit['client']['name'] ?? null;
        if ($invoiceRef !== null) {
            $parts = array_filter([$clientName, $invoiceRef]);
            $subtitle = implode(' — ', $parts);
        } else {
            $subtitle = $clientName ?? '';
        }

        $invoiceId = $hit['invoice']['id'] ?? null;
        $url = $invoiceId !== null
            ? $this->router->generate('_invoices_view', ['id' => $invoiceId])
            : $this->router->generate('_payments_index');

        return new SearchResult(
            type: 'payment',
            id: $hit['id'],
            title: $title,
            subtitle: $subtitle,
            url: $url,
            status: $hit['status'] ?? null,
            meta: isset($hit['total'])
                ? $this->moneyFormatter->format(new Money(
                    BigDecimal::of((string) $hit['total'])->multipliedBy(100)->toScale(0, RoundingMode::HalfEven)->toInt(),
                    new Currency($hit['currencyCode'] ?? $this->systemConfig->getCurrency()->getCode()),
                ))
                : null,
        );
    }
}
