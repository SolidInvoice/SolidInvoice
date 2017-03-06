<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Money\Currency;
use Money\Money;

class MoneyType extends Type
{
    const NAME = 'money';

    /**
     * @var \Money\Currency
     */
    private static $currency;

    /**
     * @param \Money\Currency $currency
     */
    public static function setCurrency(Currency $currency)
    {
	self::$currency = $currency;
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array                                     $fieldDeclaration the field declaration
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform         the currently used database platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
	return 'money';
    }
}
