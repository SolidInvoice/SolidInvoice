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
 * @method Tax create((array | callable) $attributes = [])
 * @method static Tax createOne(array $attributes = [])
 * @method static Tax find((object | array | mixed) $criteria)
 * @method static Tax findOrCreate(array $attributes)
 * @method static Tax first(string $sortedField = 'id')
 * @method static Tax last(string $sortedField = 'id')
 * @method static Tax random(array $attributes = [])
 * @method static Tax randomOrCreate(array $attributes = [])
 * @method static Tax[] all()
 * @method static Tax[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Tax[] createSequence((iterable | callable) $sequence)
 * @method static Tax[] findBy(array $attributes)
 * @method static Tax[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Tax[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Tax> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Tax> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<Tax, TaxRepository> repository()
 *
 * @phpstan-method Tax create((array | callable) $attributes = [])
 * @phpstan-method static Tax createOne(array $attributes = [])
 * @phpstan-method static Tax find((object | array | mixed) $criteria)
 * @phpstan-method static Tax findOrCreate(array $attributes)
 * @phpstan-method static Tax first(string $sortedField = 'id')
 * @phpstan-method static Tax last(string $sortedField = 'id')
 * @phpstan-method static Tax random(array $attributes = [])
 * @phpstan-method static Tax randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Tax> all()
 * @phpstan-method static list<Tax> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Tax> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Tax> findBy(array $attributes)
 * @phpstan-method static list<Tax> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Tax> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Tax> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Tax> sequence((iterable | callable) $sequence)
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
