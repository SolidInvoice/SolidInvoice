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

namespace SolidInvoice\CoreBundle\Company;

use Money\Currency as MoneyCurrency;
use function debug_backtrace;
use function dump;
use function var_dump;
use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

final class Currency
{
    private static MoneyCurrency $currency;

    public static function set(MoneyCurrency $currency): void
    {
        var_dump('Setting Currency', $currency, debug_backtrace(~DEBUG_BACKTRACE_PROVIDE_OBJECT));
        self::$currency = $currency;
    }

    public static function get(): MoneyCurrency
    {
        if (! isset(self::$currency)) {
            throw new \RuntimeException('Currency has not been set');
        }

        return self::$currency;
    }
}
