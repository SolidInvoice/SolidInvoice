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

use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Translation\TranslatableMessage;
use function is_string;

final class CurrencyFormatter implements FormatterInterface
{
    /**
     * @var string[]
     */
    private array $currencyList;

    public function __construct(
        private readonly SystemConfig $config,
        string $locale
    ) {
        $this->currencyList = Currencies::getNames($locale);
    }

    public function format(Column $column, mixed $value): string|TranslatableMessage
    {
        $systemDefault = new TranslatableMessage('System Default') . ' (' . $this->config->getCurrency()->getCode() . ')';

        if (! is_string($value)) {
            return $systemDefault;
        }

        return $this->currencyList[$value] ?? $systemDefault;
    }
}
