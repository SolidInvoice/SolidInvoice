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

use Money\Money;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Translation\TranslatableMessage;

final class MoneyFormatter implements FormatterInterface
{
    public function __construct(
        private readonly SystemConfig $config,
        private readonly \SolidInvoice\MoneyBundle\Formatter\MoneyFormatter $moneyFormatter
    ) {
    }

    public function format(Column $column, mixed $value): string|TranslatableMessage
    {
        if (! $value instanceof Money) {
            $value = new Money($value, $this->config->getCurrency());
        }

        return $this->moneyFormatter->format($value);
    }
}
