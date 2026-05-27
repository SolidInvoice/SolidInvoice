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

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use Money\Money;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter\MoneyFormatterTest
 */
final readonly class MoneyFormatter implements FormatterInterface
{
    public function __construct(
        private SystemConfig $config,
        private MoneyFormatterInterface $moneyFormatter
    ) {
    }

    public function format(Column $column, mixed $value): string|TranslatableMessage
    {
        if (! $value instanceof Money) {
            $value = new Money((string) $value, $this->config->getCurrency());
        }

        return $this->moneyFormatter->format($value);
    }
}
