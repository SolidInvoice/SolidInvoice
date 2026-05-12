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

namespace SolidInvoice\CoreBundle\Export;

use BackedEnum;
use Brick\Math\BigNumber;
use DateTimeInterface;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportMoneyNormalizer;
use Stringable;
use Symfony\Component\Uid\Ulid;

/**
 * Type-aware value formatting shared by GridRowExtractor (per-grid export) and
 * EntityRowNormalizer (full-company export). Keeps Money/DateTime/Ulid/enum
 * handling in a single place so the two export paths stay in lock-step.
 */
final class ValueFormatter
{
    /**
     * @return array{0: string, 1: string} `{$field}_amount` + `{$field}_currency`
     *                                     keys plus their values (returned as a
     *                                     `[key => value]` map below)
     *
     * @return array<string, string>
     */
    public static function flattenMoney(string $field, Money $money, ISOCurrencies $currencies): array
    {
        $exponent = $currencies->contains($money->getCurrency())
            ? $currencies->subunitFor($money->getCurrency())
            : 2;

        return [
            $field . '_amount' => ExportMoneyNormalizer::amountToDecimalString(
                BigNumber::of($money->getAmount()),
                $exponent,
            ),
            $field . '_currency' => $money->getCurrency()->getCode(),
        ];
    }

    /**
     * Returns a scalar representation for the value, or null when no shared
     * formatting applies (Money / BigNumber are caller-specific and handled by
     * the caller, not here).
     */
    public static function formatScalar(mixed $value): int|float|string|bool|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Ulid) {
            return $value->toBase58();
        }

        if (is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return null;
    }
}
