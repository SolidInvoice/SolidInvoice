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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Company|Proxy create(array|callable $attributes = [])
 * @method static Company|Proxy createOne(array $attributes = [])
 * @method static Company|Proxy find(object|array|mixed $criteria)
 * @method static Company|Proxy findOrCreate(array $attributes)
 * @method static Company|Proxy first(string $sortedField = 'id')
 * @method static Company|Proxy last(string $sortedField = 'id')
 * @method static Company|Proxy random(array $attributes = [])
 * @method static Company|Proxy randomOrCreate(array $attributes = [])
 * @method static Company[]|Proxy[] all()
 * @method static Company[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Company[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Company[]|Proxy[] findBy(array $attributes)
 * @method static Company[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Company[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Company|Proxy> many(int $min, int|null $max = null)
 * @method FactoryCollection<Company|Proxy> sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<Company, CompanyRepository> repository()
 *
 * @phpstan-method Company&Proxy<Company> create(array|callable $attributes = [])
 * @phpstan-method static Company&Proxy<Company> createOne(array $attributes = [])
 * @phpstan-method static Company&Proxy<Company> find(object|array|mixed $criteria)
 * @phpstan-method static Company&Proxy<Company> findOrCreate(array $attributes)
 * @phpstan-method static Company&Proxy<Company> first(string $sortedField = 'id')
 * @phpstan-method static Company&Proxy<Company> last(string $sortedField = 'id')
 * @phpstan-method static Company&Proxy<Company> random(array $attributes = [])
 * @phpstan-method static Company&Proxy<Company> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Company&Proxy<Company>> all()
 * @phpstan-method static list<Company&Proxy<Company>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Company&Proxy<Company>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Company&Proxy<Company>> findBy(array $attributes)
 * @phpstan-method static list<Company&Proxy<Company>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Company&Proxy<Company>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Company&Proxy<Company>> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Company&Proxy<Company>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<Company>
 */
final class CompanyFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
        ];
    }

    public static function class(): string
    {
        return Company::class;
    }
}
