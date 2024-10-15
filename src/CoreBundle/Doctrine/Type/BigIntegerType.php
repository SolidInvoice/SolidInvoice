<?php

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
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use function get_class;

final class BigIntegerType extends Type
{
    public const NAME = 'BigInteger';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        try {
            return BigInteger::of($value);
        } catch (MathException $e) {
            throw ConversionException::conversionFailedSerialization($value, $this->getName(), $e::class, $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BigNumber) {
            try {

                if (($value instanceof BigDecimal) && $value->getScale() > 0) {
                    return $value->multipliedBy(
                        100
                        /*str_pad('1', $value->getScale() + 1, '0', STR_PAD_RIGHT)*/
                    )
                        ->toScale(0, RoundingMode::HALF_EVEN)
                        ->toInt();
                }

                return $value->toScale(0, RoundingMode::HALF_EVEN)->toInt();
            } catch (MathException $e) {
                throw ConversionException::conversionFailedSerialization($value, $this->getName(), $e::class, $e);
            }
        }

        throw ConversionException::conversionFailedFormat($value, $this->getName(), get_class($value));
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
