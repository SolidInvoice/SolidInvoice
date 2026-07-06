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

namespace SolidInvoice\CoreBundle\Test\Factory;

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Company create(array|callable $attributes = [])
 * @method static Company createOne(array $attributes = [])
 * @method static Company find(object|array|mixed $criteria)
 * @method static Company findOrCreate(array $attributes)
 * @method static Company first(string $sortedField = 'id')
 * @method static Company last(string $sortedField = 'id')
 * @method static Company random(array $attributes = [])
 * @method static Company randomOrCreate(array $attributes = [])
 * @method static Company[] all()
 * @method static Company[] createMany(int $number, array|callable $attributes = [])
 * @method static Company[] createSequence(iterable|callable $sequence)
 * @method static Company[] findBy(array $attributes)
 * @method static Company[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Company[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Company> many(int $min, int|null $max = null)
 * @method FactoryCollection<Company> sequence(iterable|callable $sequence)
 * @method static RepositoryDecorator<Company, CompanyRepository> repository()
 *
 * @phpstan-method Company create(array|callable $attributes = [])
 * @phpstan-method static Company createOne(array $attributes = [])
 * @phpstan-method static Company find(object|array|mixed $criteria)
 * @phpstan-method static Company findOrCreate(array $attributes)
 * @phpstan-method static Company first(string $sortedField = 'id')
 * @phpstan-method static Company last(string $sortedField = 'id')
 * @phpstan-method static Company random(array $attributes = [])
 * @phpstan-method static Company randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Company> all()
 * @phpstan-method static list<Company> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Company> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Company> findBy(array $attributes)
 * @phpstan-method static list<Company> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Company> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Company> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Company> sequence(iterable|callable $sequence)
 * @extends PersistentObjectFactory<Company>
 */
final class CompanyFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'currency' => 'USD',
        ];
    }

    public static function class(): string
    {
        return Company::class;
    }
}
