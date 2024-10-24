<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method RecurringInvoice|Proxy create(array|callable $attributes = [])
 * @method static RecurringInvoice|Proxy createOne(array $attributes = [])
 * @method static RecurringInvoice|Proxy find(object|array|mixed $criteria)
 * @method static RecurringInvoice|Proxy findOrCreate(array $attributes)
 * @method static RecurringInvoice|Proxy first(string $sortedField = 'id')
 * @method static RecurringInvoice|Proxy last(string $sortedField = 'id')
 * @method static RecurringInvoice|Proxy random(array $attributes = [])
 * @method static RecurringInvoice|Proxy randomOrCreate(array $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] all()
 * @method static RecurringInvoice[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static RecurringInvoice[]|Proxy[] findBy(array $attributes)
 * @method static RecurringInvoice[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<RecurringInvoice|Proxy> many(int $min, int|null $max = null)
 * @method FactoryCollection<RecurringInvoice|Proxy> sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<RecurringInvoice, RecurringInvoiceRepository> repository()
 *
 * @phpstan-method RecurringInvoice&Proxy<RecurringInvoice> create(array|callable $attributes = [])
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> createOne(array $attributes = [])
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> find(object|array|mixed $criteria)
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> findOrCreate(array $attributes)
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> first(string $sortedField = 'id')
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> last(string $sortedField = 'id')
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> random(array $attributes = [])
 * @phpstan-method static RecurringInvoice&Proxy<RecurringInvoice> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> all()
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> findBy(array $attributes)
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<RecurringInvoice&Proxy<RecurringInvoice>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<RecurringInvoice&Proxy<RecurringInvoice>> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<RecurringInvoice&Proxy<RecurringInvoice>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<RecurringInvoice>
 */
final class RecurringInvoiceFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'status' => self::faker()->word(),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'dateStart' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => (new Discount())
                ->setType(self::faker()->text())
                ->setValueMoney(BigInteger::of(self::faker()->randomNumber()))
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    public static function class(): string
    {
        return RecurringInvoice::class;
    }
}
