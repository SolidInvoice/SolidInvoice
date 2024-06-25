<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use Money\Currency;
use Money\Money;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\SettingsBundle\SystemConfig;

final class MoneyFormatter implements FormatterInterface
{
    public function __construct(
        private readonly SystemConfig $config,
        private readonly \SolidInvoice\MoneyBundle\Formatter\MoneyFormatter $moneyFormatter
    ) {
    }

    public function format(Column $column, mixed $value): string
    {
        if (! $value instanceof Money) {
            $value = new Money($value, new Currency($this->config->getCurrency()));
        }

        return $this->moneyFormatter->format($value);
    }
}
