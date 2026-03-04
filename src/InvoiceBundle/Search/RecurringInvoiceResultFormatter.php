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

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class RecurringInvoiceResultFormatter implements ResultFormatterInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function getIndexName(): string
    {
        return 'recurring_invoices';
    }

    public function format(array $hit): SearchResult
    {
        return new SearchResult(
            type: 'recurring_invoice',
            id: $hit['id'],
            title: $hit['clientName'] ?? $hit['id'],
            subtitle: $hit['status'] ?? '',
            url: $this->router->generate('_invoices_view_recurring', ['id' => $hit['id']]),
            status: $hit['status'] ?? null,
            meta: isset($hit['total'])
                ? $this->moneyFormatter->format(new Money(
                    BigDecimal::of((string) $hit['total'])->multipliedBy(100)->toScale(0, RoundingMode::HALF_EVEN)->toInt(),
                    new Currency($hit['currencyCode'] ?? $this->systemConfig->getCurrency()->getCode()),
                ))
                : null,
        );
    }
}
