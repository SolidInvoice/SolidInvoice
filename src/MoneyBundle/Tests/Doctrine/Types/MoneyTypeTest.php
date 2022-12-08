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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
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
        if (! Type::hasType('money')) {
            Type::addType('money', MoneyType::class);
        }

        MoneyType::setCurrency(new Currency('USD'));
    }

    public function testGetName(): void
    {
        /** @var MoneyType $type */
        $type = Type::getType('money');

        self::assertSame('money', $type->getName());
    }

    public function testGetSQLDeclaration(): void
    {
        $platform = M::mock(AbstractPlatform::class);
        $platform->shouldReceive('getIntegerTypeDeclarationSQL', [])
            ->once();

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $type->getSQLDeclaration([], $platform);
    }

    public function testConvertToPHPValue(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToPHPValue(1200, $platform);

        self::assertInstanceOf(Money::class, $value);
        self::assertSame('1200', $value->getAmount());
        self::assertSame('USD', $value->getCurrency()->getCode());
    }

    public function testConvertToPHPValueWithEmptyValue(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToPHPValue(null, $platform);

        self::assertInstanceOf(Money::class, $value);
        self::assertSame('0', $value->getAmount());
    }

    public function testConvertToPHPValueWithMoneyValue(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $money = new Money(1200, new Currency('USD'));
        $value = $type->convertToPHPValue($money, $platform);

        self::assertSame($money, $value);
    }

    public function testConvertToDatabaseValueWithEmptyValue(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $value = $type->convertToDatabaseValue(null, $platform);

        self::assertSame(0, $value);
    }

    public function testConvertToDatabaseValueWithMoney(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $money = new Money(1200, new Currency('USD'));
        $value = $type->convertToDatabaseValue($money, $platform);

        self::assertSame('1200', $value);
    }

    public function testConvertToDatabaseValueInvalidValue(): void
    {
        $platform = M::mock(AbstractPlatform::class);

        /** @var MoneyType $type */
        $type = Type::getType('money');

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Could not convert database value "0.2" to Doctrine Type money');

        $type->convertToDatabaseValue(0.2, $platform);
    }
}
