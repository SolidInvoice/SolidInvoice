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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Type;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;

final class QuantityTypeTest extends TestCase
{
    private QuantityType $type;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new QuantityType();
        $this->platform = new MySQLPlatform();
    }

    /**
     * @return iterable<string, array{AbstractPlatform, string}>
     */
    public static function platforms(): iterable
    {
        yield 'mysql' => [new MySQLPlatform(), 'NUMERIC(20, 6)'];
        yield 'postgres' => [new PostgreSQLPlatform(), 'NUMERIC(20, 6)'];
        yield 'sqlite' => [new SQLitePlatform(), 'NUMERIC(20, 6)'];
    }

    #[DataProvider('platforms')]
    public function testDeclaresAFixedPrecisionAndScale(AbstractPlatform $platform, string $expected): void
    {
        // The precision and scale in the mapping are deliberately ignored, so the DDL can
        // never drift from the scale convertToDatabaseValue() rounds to.
        self::assertSame($expected, $this->type->getSQLDeclaration(['precision' => 4, 'scale' => 1], $platform));
    }

    public function testConvertsNullBothWays(): void
    {
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    /**
     * @return iterable<string, array{int|float|string, string}>
     */
    public static function databaseValues(): iterable
    {
        yield 'padded decimal string' => ['2.500000', '2.5'];
        yield 'whole number string' => ['1.000000', '1'];
        yield 'full scale' => ['0.000001', '0.000001'];
        yield 'largest supported value' => ['99999999999999.999999', '99999999999999.999999'];
        yield 'negative' => ['-3.250000', '-3.25'];
        yield 'zero' => ['0.000000', '0'];
        // SQLite has no exact decimal storage class and hands back native types instead.
        yield 'sqlite float' => [2.5, '2.5'];
        yield 'sqlite float needing the full scale' => [1.234567, '1.234567'];
        yield 'sqlite integer' => [3, '3'];
    }

    #[DataProvider('databaseValues')]
    public function testConvertsToPhpValue(int | float | string $value, string $expected): void
    {
        $converted = $this->type->convertToPHPValue($value, $this->platform);

        self::assertInstanceOf(BigDecimal::class, $converted);
        // Trailing zeros are stripped so `{{ line.qty }}` renders "2.5", not "2.500000".
        self::assertSame($expected, (string) $converted);
    }

    /**
     * @return iterable<string, array{BigDecimal|BigInteger, string}>
     */
    public static function phpValues(): iterable
    {
        yield 'integer' => [BigInteger::of(3), '3.000000'];
        yield 'one decimal' => [BigDecimal::of('2.5'), '2.500000'];
        yield 'full scale' => [BigDecimal::of('0.000001'), '0.000001'];
        yield 'beyond scale rounds half even down' => [BigDecimal::of('0.0000005'), '0.000000'];
        yield 'beyond scale rounds half even up' => [BigDecimal::of('0.0000015'), '0.000002'];
        yield 'negative' => [BigDecimal::of('-3.25'), '-3.250000'];
    }

    #[DataProvider('phpValues')]
    public function testConvertsToDatabaseValue(BigDecimal | BigInteger $value, string $expected): void
    {
        self::assertSame($expected, $this->type->convertToDatabaseValue($value, $this->platform));
    }

    public function testRejectsNonNumbers(): void
    {
        $this->expectException(InvalidType::class);

        $this->type->convertToDatabaseValue('2.5', $this->platform);
    }

    #[DataProvider('databaseValues')]
    public function testRoundTripsExactly(int | float | string $value): void
    {
        $php = $this->type->convertToPHPValue($value, $this->platform);
        self::assertNotNull($php);

        $database = $this->type->convertToDatabaseValue($php, $this->platform);
        self::assertNotNull($database);

        self::assertTrue($php->isEqualTo(BigDecimal::of($database)));
    }
}
