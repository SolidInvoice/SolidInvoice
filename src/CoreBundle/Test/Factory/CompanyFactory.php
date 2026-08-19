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
 * @method Company create(array<string, mixed>|callable $attributes = [])
 * @method static Company createOne(array<string, mixed> $attributes = [])
 * @method static Company find(object|array<string, mixed>|mixed $criteria)
 * @method static Company findOrCreate(array<string, mixed> $attributes)
 * @method static Company first(string $sortedField = 'id')
 * @method static Company last(string $sortedField = 'id')
 * @method static Company random(array<string, mixed> $attributes = [])
 * @method static Company randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Company[] all()
 * @method static Company[] createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @method static Company[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Company[] findBy(array<string, mixed> $attributes)
 * @method static Company[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Company[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Company, CompanyFactory> many(int $min, int|null $max = null)
 * @method FactoryCollection<Company, CompanyFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Company, CompanyRepository> repository()
 *
 * @phpstan-method Company create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Company createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Company find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Company findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Company first(string $sortedField = 'id')
 * @phpstan-method static Company last(string $sortedField = 'id')
 * @phpstan-method static Company random(array<string, mixed> $attributes = [])
 * @phpstan-method static Company randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Company> all()
 * @phpstan-method static list<Company> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Company> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Company> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Company> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Company> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Company, CompanyFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Company, CompanyFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
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
