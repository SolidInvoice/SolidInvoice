<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Company;

use Money\Currency as MoneyCurrency;

final class Currency
{
    private static MoneyCurrency $currency;

    public static function set(MoneyCurrency $currency): void
    {
        self::$currency = $currency;
    }

    public static function get(): MoneyCurrency
    {
        if (!isset(self::$currency)) {
            throw new \RuntimeException('Currency has not been set');
        }

        return self::$currency;
    }
}
