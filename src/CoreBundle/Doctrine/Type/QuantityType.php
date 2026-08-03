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

namespace SolidInvoice\CoreBundle\Doctrine\Type;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Override;
use function is_float;
use function sprintf;

/**
 * Exact decimal quantity, stored as `DECIMAL(20, 6)` and exposed as a {@see BigNumber}.
 *
 * Quantities feed straight into the line total (`price × qty`), so they have to be as
 * exact as the monetary values they multiply — see {@see BigIntegerType}. A float column
 * cannot do that: the stored value is rounded to whatever the nearest IEEE-754 double is,
 * and reading it back out through `(string)` re-rounds it at PHP's `precision` ini
 * setting, so the same row can produce different totals on differently configured hosts.
 *
 * ### Why scale 6
 *
 * Six decimal places covers every quantity we have seen billed in practice, with headroom:
 *
 * - fractional hours to four places (`0.0833` = 5 minutes) — the case that motivated this;
 * - metered usage priced per unit (bandwidth, API calls, storage-hours);
 * - weights and volumes in metric units (a milligram is `0.000001` kg).
 *
 * It is also comfortably above what e-invoicing formats expect of a quantity: Peppol BIS
 * Billing 3.0 and Factur-X/ZUGFeRD both cap `InvoicedQuantity` at four decimals, so a
 * SolidInvoice line can always be represented in those formats without rounding.
 *
 * ### Why precision 20
 *
 * 20 total digits leaves 14 for the integral part — a hundred trillion units — which no
 * realistic line item approaches, while staying inside the limits of every platform we
 * support (MySQL caps `DECIMAL` at 65 digits, PostgreSQL far higher). 20 is also the
 * number of digits in a 64-bit integer, matching the `BIGINT` columns the monetary
 * amounts use, so the two halves of `price × qty` have symmetric headroom.
 *
 * ### SQLite
 *
 * SQLite has no exact decimal storage class: a `NUMERIC(20, 6)` column falls back to
 * `REAL` (a double) for fractional values. Quantities are therefore only exact on SQLite
 * up to a double's ~15 significant digits — well beyond anything enterable, but not the
 * full 20 the column advertises. MySQL, MariaDB and PostgreSQL store the full precision.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Type\QuantityTypeTest
 */
final class QuantityType extends Type
{
    public const string NAME = 'Quantity';

    public const int PRECISION = 20;

    public const int SCALE = 6;

    /**
     * The constants are a default, not an override: a caller that declares its own
     * precision and scale — the entity mapping, or a migration freezing the shape it
     * produced — gets exactly what it asked for. Declaring nothing gets `DECIMAL(20, 6)`,
     * so a column can never silently end up narrower than the scale
     * {@see self::convertToDatabaseValue()} rounds to.
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL([
            'precision' => $column['precision'] ?? self::PRECISION,
            'scale' => $column['scale'] ?? self::SCALE,
        ]);
    }

    /**
     * The single rule for what a quantity is allowed to be: at most {@see self::SCALE}
     * decimal places, rounded half-even, in canonical form.
     *
     * Every entry point has to apply this at the moment the quantity is set, not when it
     * is written. A line total is computed in `PrePersist`, so a quantity carrying more
     * decimals than the column can hold would be multiplied at full precision and stored
     * against a rounded quantity — reloading the invoice and recomputing would then give a
     * different total.
     *
     * @throws MathException
     */
    public static function normalize(BigNumber $quantity): BigDecimal
    {
        return $quantity->toBigDecimal()
            ->toScale(self::SCALE, RoundingMode::HalfEven)
            ->strippedOfTrailingZeros();
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BigDecimal
    {
        if ($value === null) {
            return null;
        }

        try {
            // SQLite hands back a float or an int for this column; every other platform
            // returns the decimal as a string. Formatting the float to the column scale
            // recovers the stored value without going through PHP's `precision` setting.
            return self::normalize(BigDecimal::of(is_float($value) ? sprintf('%.*F', self::SCALE, $value) : $value));
        } catch (MathException $e) {
            throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
        }
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BigNumber) {
            try {
                // toScale() only pads here: normalize() has already rounded to the scale.
                return (string) self::normalize($value)->toScale(self::SCALE);
            } catch (MathException $e) {
                throw SerializationFailed::new($value, self::NAME, $e->getMessage(), $e);
            }
        }

        throw InvalidType::new($value, self::NAME, [BigNumber::class]);
    }
}
