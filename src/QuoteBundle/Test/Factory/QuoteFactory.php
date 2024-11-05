<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Quote|Proxy create((array | callable) $attributes = [])
 * @method static Quote|Proxy createOne(array $attributes = [])
 * @method static Quote|Proxy find((object | array | mixed) $criteria)
 * @method static Quote|Proxy findOrCreate(array $attributes)
 * @method static Quote|Proxy first(string $sortedField = 'id')
 * @method static Quote|Proxy last(string $sortedField = 'id')
 * @method static Quote|Proxy random(array $attributes = [])
 * @method static Quote|Proxy randomOrCreate(array $attributes = [])
 * @method static Quote[]|Proxy[] all()
 * @method static Quote[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Quote[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static Quote[]|Proxy[] findBy(array $attributes)
 * @method static Quote[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Quote[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Quote | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Quote | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Quote, QuoteRepository> repository()
 *
 * @phpstan-method Quote&Proxy<Quote> create((array | callable) $attributes = [])
 * @phpstan-method static Quote&Proxy<Quote> createOne(array $attributes = [])
 * @phpstan-method static Quote&Proxy<Quote> find((object | array | mixed) $criteria)
 * @phpstan-method static Quote&Proxy<Quote> findOrCreate(array $attributes)
 * @phpstan-method static Quote&Proxy<Quote> first(string $sortedField = 'id')
 * @phpstan-method static Quote&Proxy<Quote> last(string $sortedField = 'id')
 * @phpstan-method static Quote&Proxy<Quote> random(array $attributes = [])
 * @phpstan-method static Quote&Proxy<Quote> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Quote&Proxy<Quote>> all()
 * @phpstan-method static list<Quote&Proxy<Quote>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Quote&Proxy<Quote>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Quote&Proxy<Quote>> findBy(array $attributes)
 * @phpstan-method static list<Quote&Proxy<Quote>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Quote&Proxy<Quote>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Quote&Proxy<Quote>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Quote&Proxy<Quote>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Quote>
 */
final class QuoteFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'company' => CompanyFactory::new(),
            'due' => self::faker()->dateTime(),
            'status' => self::faker()->word(),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => (new Discount())
                ->setType(self::faker()->text())
                ->setValueMoney(self::faker()->randomNumber())
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    public static function class(): string
    {
        return Quote::class;
    }
}
