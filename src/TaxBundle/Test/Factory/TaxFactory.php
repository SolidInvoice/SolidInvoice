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

namespace SolidInvoice\TaxBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Tax create((array<string, mixed> | callable) $attributes = [])
 * @method static Tax createOne(array<string, mixed> $attributes = [])
 * @method static Tax find((object | array<string, mixed> | mixed) $criteria)
 * @method static Tax findOrCreate(array<string, mixed> $attributes)
 * @method static Tax first(string $sortedField = 'id')
 * @method static Tax last(string $sortedField = 'id')
 * @method static Tax random(array<string, mixed> $attributes = [])
 * @method static Tax randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Tax[] all()
 * @method static Tax[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static Tax[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Tax[] findBy(array<string, mixed> $attributes)
 * @method static Tax[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Tax[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Tax, TaxFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Tax, TaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Tax, TaxRepository> repository()
 *
 * @phpstan-method Tax create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Tax createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Tax find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Tax findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Tax first(string $sortedField = 'id')
 * @phpstan-method static Tax last(string $sortedField = 'id')
 * @phpstan-method static Tax random(array<string, mixed> $attributes = [])
 * @phpstan-method static Tax randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Tax> all()
 * @phpstan-method static list<Tax> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Tax> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Tax> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Tax> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Tax> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Tax, TaxFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Tax, TaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<Tax>
 */
final class TaxFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->word(),
            'rate' => self::faker()->randomFloat(2, 0, 100),
            'type' => self::faker()->randomElement([Tax::TYPE_INCLUSIVE, Tax::TYPE_EXCLUSIVE, Tax::TYPE_FLAT_RATE]),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Tax::class;
    }
}
