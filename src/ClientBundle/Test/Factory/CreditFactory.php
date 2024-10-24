<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Test\Factory;

use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Credit|Proxy create(array|callable $attributes = [])
 * @method static Credit|Proxy createOne(array $attributes = [])
 * @method static Credit|Proxy find(object|array|mixed $criteria)
 * @method static Credit|Proxy findOrCreate(array $attributes)
 * @method static Credit|Proxy first(string $sortedField = 'id')
 * @method static Credit|Proxy last(string $sortedField = 'id')
 * @method static Credit|Proxy random(array $attributes = [])
 * @method static Credit|Proxy randomOrCreate(array $attributes = [])
 * @method static Credit[]|Proxy[] all()
 * @method static Credit[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Credit[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Credit[]|Proxy[] findBy(array $attributes)
 * @method static Credit[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Credit[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Credit|Proxy> many(int $min, int|null $max = null)
 * @method FactoryCollection<Credit|Proxy> sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<Credit, CreditRepository> repository()
 *
 * @phpstan-method Credit&Proxy<Credit> create(array|callable $attributes = [])
 * @phpstan-method static Credit&Proxy<Credit> createOne(array $attributes = [])
 * @phpstan-method static Credit&Proxy<Credit> find(object|array|mixed $criteria)
 * @phpstan-method static Credit&Proxy<Credit> findOrCreate(array $attributes)
 * @phpstan-method static Credit&Proxy<Credit> first(string $sortedField = 'id')
 * @phpstan-method static Credit&Proxy<Credit> last(string $sortedField = 'id')
 * @phpstan-method static Credit&Proxy<Credit> random(array $attributes = [])
 * @phpstan-method static Credit&Proxy<Credit> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Credit&Proxy<Credit>> all()
 * @phpstan-method static list<Credit&Proxy<Credit>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Credit&Proxy<Credit>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Credit&Proxy<Credit>> findBy(array $attributes)
 * @phpstan-method static list<Credit&Proxy<Credit>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Credit&Proxy<Credit>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Credit&Proxy<Credit>> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Credit&Proxy<Credit>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<Credit>
 */
final class CreditFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'value' => new Money(self::faker()->randomNumber(), new Currency(self::faker()->currencyCode())),
            'company' => CompanyFactory::new(),
        ];
    }

    public static function class(): string
    {
        return Credit::class;
    }
}
