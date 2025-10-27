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

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Type;
use function get_class;

final class BigIntegerType extends Type
{
    public const string NAME = 'BigInteger';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BigInteger
    {
        if ($value === null) {
            return null;
        }

        try {
            return BigInteger::of($value);
        } catch (MathException $e) {
            throw SerializationFailed::new($value, $this->getName(), $e->getMessage(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BigNumber) {
            try {
                return $value->toScale(0, RoundingMode::HALF_EVEN)->toInt();
            } catch (MathException $e) {
                throw SerializationFailed::new($value, $this->getName(), $e->getMessage(), $e);
            }
        }

        throw InvalidFormat::new($value, $this->getName(), get_class($value));
    }
}
