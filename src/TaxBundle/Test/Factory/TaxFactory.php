<?php

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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Tax|Proxy<Tax> create((array | callable) $attributes = [])
 * @method static Tax|Proxy<Tax> createOne(array $attributes = [])
 * @method static Tax|Proxy<Tax> find((object | array | mixed) $criteria)
 * @method static Tax|Proxy<Tax> findOrCreate(array $attributes)
 * @method static Tax|Proxy<Tax> first(string $sortedField = 'id')
 * @method static Tax|Proxy<Tax> last(string $sortedField = 'id')
 * @method static Tax|Proxy<Tax> random(array $attributes = [])
 * @method static Tax|Proxy<Tax> randomOrCreate(array $attributes = [])
 * @method static Tax[]|Proxy<Tax>[] all()
 * @method static Tax[]|Proxy<Tax>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Tax[]|Proxy<Tax>[] createSequence((iterable | callable) $sequence)
 * @method static Tax[]|Proxy<Tax>[] findBy(array $attributes)
 * @method static Tax[]|Proxy<Tax>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Tax[]|Proxy<Tax>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Tax | Proxy<Tax>)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Tax | Proxy<Tax>)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Tax, TaxRepository> repository()
 *
 * @phpstan-method Tax&Proxy<Tax> create((array | callable) $attributes = [])
 * @phpstan-method static Tax&Proxy<Tax> createOne(array $attributes = [])
 * @phpstan-method static Tax&Proxy<Tax> find((object | array | mixed) $criteria)
 * @phpstan-method static Tax&Proxy<Tax> findOrCreate(array $attributes)
 * @phpstan-method static Tax&Proxy<Tax> first(string $sortedField = 'id')
 * @phpstan-method static Tax&Proxy<Tax> last(string $sortedField = 'id')
 * @phpstan-method static Tax&Proxy<Tax> random(array $attributes = [])
 * @phpstan-method static Tax&Proxy<Tax> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Tax&Proxy<Tax>> all()
 * @phpstan-method static list<Tax&Proxy<Tax>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Tax&Proxy<Tax>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Tax&Proxy<Tax>> findBy(array $attributes)
 * @phpstan-method static list<Tax&Proxy<Tax>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Tax&Proxy<Tax>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Tax&Proxy<Tax>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Tax&Proxy<Tax>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Tax>
 */
final class TaxFactory extends PersistentProxyObjectFactory
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
