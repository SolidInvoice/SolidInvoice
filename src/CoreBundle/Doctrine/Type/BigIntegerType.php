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

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Override;

final class BigIntegerType extends Type
{
    public const string NAME = 'BigInteger';

    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            return BigInteger::of($value);
        } catch (MathException $e) {
            throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
        }
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BigNumber) {
            try {
                return $value->toScale(0, RoundingMode::HalfEven)->toInt();
            } catch (MathException $e) {
                throw SerializationFailed::new($value, self::NAME, $e->getMessage(), $e);
            }
        }

        throw InvalidType::new($value, self::NAME, [BigNumber::class]);
    }
}
