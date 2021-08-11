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

namespace SolidInvoice\MoneyBundle\Factory;

use Money\Currency;
use SolidInvoice\SettingsBundle\SystemConfig;

class CurrencyFactory
{
    public const CURRENCY_PATH = 'system/company/currency';

    public const DEFAULT_CURRENCY = 'USD';

    /**
     * @var SystemConfig
     */
    private $config;

    /**
     * @var string
     */
    private $installed;

    public function __construct(?string $installed, SystemConfig $config)
    {
        $this->config = $config;
        $this->installed = $installed;
    }

    public function getCurrency(): Currency
    {
        return new Currency(null !== $this->installed ? ($this->config->get(self::CURRENCY_PATH) ?? self::DEFAULT_CURRENCY) : self::DEFAULT_CURRENCY);
    }
}
