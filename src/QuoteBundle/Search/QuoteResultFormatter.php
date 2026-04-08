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

namespace SolidInvoice\QuoteBundle\Search;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class QuoteResultFormatter implements QualifiedResultFormatterInterface
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
            'amount' => 'total',
            'client' => 'client.name',
            'created' => 'created',
        ];
    }

    public function getIndexName(): string
    {
        return 'quotes';
    }

    public function format(array $hit): SearchResult
    {
        return new SearchResult(
            type: 'quote',
            id: $hit['id'],
            title: $hit['quoteId'] ?? $hit['id'],
            subtitle: $hit['client']['name'] ?? '',
            url: $this->router->generate('_quotes_view', ['id' => $hit['id']]),
            status: $hit['status'] ?? null,
            meta: isset($hit['total'])
                ? $this->moneyFormatter->format(new Money(
                    BigDecimal::of((string) $hit['total'])->multipliedBy(100)->toScale(0, RoundingMode::HalfEven)->toInt(),
                    new Currency($hit['client']['currencyCode'] ?? $this->systemConfig->getCurrency()->getCode()),
                ))
                : null,
        );
    }
}
