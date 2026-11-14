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

namespace SolidInvoice\ProductBundle\Test\Factory;

use Brick\Math\BigDecimal;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Product create((array<string, mixed> | callable) $attributes = [])
 * @method static Product createOne(array<string, mixed> $attributes = [])
 * @method static Product find((object | array<string, mixed> | mixed) $criteria)
 * @method static Product findOrCreate(array<string, mixed> $attributes)
 * @method static Product first(string $sortedField = 'id')
 * @method static Product last(string $sortedField = 'id')
 * @method static Product random(array<string, mixed> $attributes = [])
 * @method static Product randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Product[] all()
 * @method static Product[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static Product[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Product[] findBy(array<string, mixed> $attributes)
 * @method static Product[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Product[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Product, ProductFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Product, ProductFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Product, ProductRepository> repository()
 *
 * @phpstan-method Product create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Product createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Product find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Product findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Product first(string $sortedField = 'id')
 * @phpstan-method static Product last(string $sortedField = 'id')
 * @phpstan-method static Product random(array<string, mixed> $attributes = [])
 * @phpstan-method static Product randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Product> all()
 * @phpstan-method static list<Product> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Product> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Product> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Product> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Product> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Product, ProductFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Product, ProductFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->words(2, true),
            'price' => BigDecimal::of('100.00'),
            'quantity' => BigDecimal::one(),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Product::class;
    }
}
