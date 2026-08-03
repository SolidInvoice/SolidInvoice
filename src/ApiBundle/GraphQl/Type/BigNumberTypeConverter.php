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

namespace SolidInvoice\ApiBundle\GraphQl\Type;

use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use Brick\Math\BigNumber;
use GraphQL\Type\Definition\Type as GraphQLType;
use Override;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BigIntegerNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Maps {@see BigNumber} properties to the GraphQL `Float` type.
 *
 * `BigNumber` is not an API Platform resource, so the stock converter has nothing to map it
 * to and the schema builder falls back to `Int`. That is wrong for every one of them:
 * {@see BigIntegerNormalizer} emits a float — a monetary amount divided back out of the
 * minor unit, or a line quantity, which can carry six decimal places.
 *
 * @see \SolidInvoice\ApiBundle\Tests\GraphQl\Type\BigNumberTypeConverterTest
 */
#[AsDecorator(decorates: 'api_platform.graphql.type_converter')]
final readonly class BigNumberTypeConverter implements TypeConverterInterface
{
    public function __construct(
        private TypeConverterInterface $decorated,
    ) {
    }

    /**
     * Deprecated in API Platform 4.2, and dead on this stack: the interface types this
     * parameter as `Symfony\Component\PropertyInfo\Type`, which Symfony 8 removed in
     * favour of TypeInfo. The parameter is therefore declared `mixed` — naming a class
     * that does not exist would be a lie — and the call is passed straight through.
     *
     * @param mixed $type a `Symfony\Component\PropertyInfo\Type` on Symfony 7 and below
     */
    #[Override]
    public function convertType(mixed $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth): GraphQLType | string | null
    {
        return $this->decorated->convertType($type, $input, $rootOperation, $resourceClass, $rootResource, $property, $depth);
    }

    public function convertPhpType(Type $type, bool $input, Operation $rootOperation, string $resourceClass, string $rootResource, ?string $property, int $depth): GraphQLType | string | null
    {
        if ($type->isIdentifiedBy(TypeIdentifier::OBJECT) && $type->isIdentifiedBy(BigNumber::class)) {
            return GraphQLType::float();
        }

        return $this->decorated->convertPhpType($type, $input, $rootOperation, $resourceClass, $rootResource, $property, $depth);
    }

    #[Override]
    public function resolveType(string $type): ?GraphQLType
    {
        return $this->decorated->resolveType($type);
    }
}
