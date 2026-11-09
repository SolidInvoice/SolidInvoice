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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Brick\Math\BigInteger;
use Brick\Math\Exception\RoundingNecessaryException;
use Mcp\Exception\ToolCallException;
use Money\Currency;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use function get_debug_type;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * Reads a money amount a tool was given - a decimal string in the document's currency, such as
 * "12.50" - into the minor units it is stored in. More precision than the currency has is
 * rejected rather than rounded, so "1.234" is fine for BHD but not for USD.
 *
 * A negative amount is accepted only where the domain allows one ($signed): a credit line or a
 * discount can be negative, a payment cannot.
 */
final readonly class MoneyInput
{
    public function __construct(
        private CurrencyScale $currencyScale,
    ) {
    }

    public function toMinorUnits(mixed $value, Currency $currency, string $field, bool $signed = false): BigInteger
    {
        if (! is_string($value)) {
            throw new ToolCallException(sprintf('%s must be a decimal string such as "12.50", not %s.', $field, get_debug_type($value)));
        }

        if (preg_match($signed ? '/^-?\d+(\.\d+)?\z/' : '/^\d+(\.\d+)?\z/', $value) !== 1) {
            throw new ToolCallException(sprintf('%s "%s" is not a %sdecimal amount such as "12.50".', $field, $value, $signed ? '' : 'non-negative '));
        }

        try {
            return $this->currencyScale->toMinorUnit($value, $currency)->toBigInteger();
        } catch (RoundingNecessaryException) {
            throw new ToolCallException(sprintf(
                '%s "%s" has more decimal places than %s allows (%d).',
                $field,
                $value,
                $currency->getCode(),
                $this->currencyScale->subunitFor($currency),
            ));
        }
    }
}
