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

namespace SolidInvoice\MoneyBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Money\Currency;
use Money\Money;

class MoneyType extends Type
{
    public const NAME = 'money';

    /**
     * @var Currency
     */
    private static $currency;

    public static function setCurrency(Currency $currency)
    {
        self::$currency = $currency;
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array            $fieldDeclaration the field declaration
     * @param AbstractPlatform $platform         the currently used database platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): ?string
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof Money) {
            return $value;
        }

        if (0 === (int) $value) {
            return new Money(0, self::$currency);
        }

        return new Money((int) $value, self::$currency);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return 0;
        }

        if ($value instanceof Money) {
            return $value->getAmount();
        }

        if ((int) $value > 0) {
            return (int) $value;
        }

        throw ConversionException::conversionFailed($value, self::NAME);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'money';
    }
}
