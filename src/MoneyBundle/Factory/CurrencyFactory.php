<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Factory;

use CSBill\SettingsBundle\SystemConfig;
use Money\Currency;

class CurrencyFactory
{
    const CURRENCY_PATH = 'system/general/currency';

    /**
     * @var SystemConfig
     */
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return new Currency($this->config->get(self::CURRENCY_PATH));
    }
}