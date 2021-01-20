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

namespace SolidInvoice\MoneyBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Doctrine\Types\MoneyType;

class MoneyTypeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        if (!Type::hasType('money')) {
            Type::addType('money', 'SolidInvoice\MoneyBundle\Doctrine\Types\MoneyType');
        }

        MoneyType::setCurrency(new Currency('USD'));
    }

    public function testGetName()
    {
        /** @var MoneyType $type */
        $type = Type::getType('money');

        static::assertSame('money', $type->getName());
    }

    public function testGetSQLDeclaration()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');
        $platform->shouldReceive('getIntegerTypeDeclarationSQL', [])
            ->once();

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $type->getSQLDeclaration([], $platform);
    }

    public function testConvertToPHPValue()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToPHPValue(1200, $platform);

        static::assertInstanceOf('Money\Money', $value);
        static::assertSame('1200', $value->getAmount());
        static::assertSame('USD', $value->getCurrency()->getCode());
    }

    public function testConvertToPHPValueWithEmptyValue()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToPHPValue(null, $platform);

        static::assertInstanceOf('Money\Money', $value);
        static::assertSame('0', $value->getAmount());
    }

    public function testConvertToPHPValueWithMoneyValue()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $money = new Money(1200, new Currency('USD'));
        $value = $type->convertToPHPValue($money, $platform);

        static::assertSame($money, $value);
    }

    public function testConvertToDatabaseValueWithEmptyValue()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToDatabaseValue(null, $platform);

        static::assertSame(0, $value);
    }

    public function testConvertToDatabaseValueWithMoney()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $money = new Money(1200, new Currency('USD'));
        $value = $type->convertToDatabaseValue($money, $platform);

        static::assertSame('1200', $value);
    }

    public function testConvertToDatabaseValueInvalidValue()
    {
        $platform = M::mock('Doctrine\DBAL\Platforms\AbstractPlatform');

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $this->expectException('Doctrine\Dbal\Types\ConversionException');
        $this->expectExceptionMessage('Could not convert database value "0.2" to Doctrine Type money');

        $type->convertToDatabaseValue(0.2, $platform);
    }
}
