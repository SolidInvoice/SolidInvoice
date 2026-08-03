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

namespace SolidInvoice\ApiBundle\Tests\GraphQl\Type;

use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use GraphQL\Type\Definition\Type as GraphQLType;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\GraphQl\Type\BigNumberTypeConverter;
use SolidInvoice\InvoiceBundle\Entity\Line;
use Symfony\Component\TypeInfo\Type;

final class BigNumberTypeConverterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * `BigNumber` is not a resource, so without this the schema builder falls back to
     * `Int` — which cannot carry a fractional quantity or a de-scaled money amount.
     */
    public function testConvertsBigNumberToFloat(): void
    {
        $decorated = M::mock(TypeConverterInterface::class);
        $decorated->shouldNotReceive('convertPhpType');

        $converter = new BigNumberTypeConverter($decorated);

        self::assertSame(
            GraphQLType::float(),
            $converter->convertPhpType(Type::object(BigNumber::class), false, new Query(), Line::class, Line::class, 'qty', 0)
        );
    }

    public function testConvertsBigNumberSubclassesToFloat(): void
    {
        $decorated = M::mock(TypeConverterInterface::class);
        $decorated->shouldNotReceive('convertPhpType');

        $converter = new BigNumberTypeConverter($decorated);

        self::assertSame(
            GraphQLType::float(),
            $converter->convertPhpType(Type::object(BigDecimal::class), false, new Query(), Line::class, Line::class, 'qty', 0)
        );
    }

    public function testDelegatesEverythingElse(): void
    {
        $expected = GraphQLType::string();

        $decorated = M::mock(TypeConverterInterface::class);
        $decorated->shouldReceive('convertPhpType')
            ->once()
            ->andReturn($expected);

        $converter = new BigNumberTypeConverter($decorated);

        self::assertSame(
            $expected,
            $converter->convertPhpType(Type::string(), false, new Query(), Line::class, Line::class, 'description', 0)
        );
    }

    public function testResolveTypeIsDelegated(): void
    {
        $expected = GraphQLType::int();

        $decorated = M::mock(TypeConverterInterface::class);
        $decorated->shouldReceive('resolveType')
            ->once()
            ->with('Int')
            ->andReturn($expected);

        self::assertSame($expected, new BigNumberTypeConverter($decorated)->resolveType('Int'));
    }
}
