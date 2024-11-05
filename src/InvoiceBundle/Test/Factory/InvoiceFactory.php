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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Invoice|Proxy create((array | callable) $attributes = [])
 * @method static Invoice|Proxy createOne(array $attributes = [])
 * @method static Invoice|Proxy find((object | array | mixed) $criteria)
 * @method static Invoice|Proxy findOrCreate(array $attributes)
 * @method static Invoice|Proxy first(string $sortedField = 'id')
 * @method static Invoice|Proxy last(string $sortedField = 'id')
 * @method static Invoice|Proxy random(array $attributes = [])
 * @method static Invoice|Proxy randomOrCreate(array $attributes = [])
 * @method static Invoice[]|Proxy[] all()
 * @method static Invoice[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Invoice[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static Invoice[]|Proxy[] findBy(array $attributes)
 * @method static Invoice[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Invoice[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Invoice | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Invoice | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Invoice, InvoiceRepository> repository()
 *
 * @phpstan-method Invoice&Proxy<Invoice> create((array | callable) $attributes = [])
 * @phpstan-method static Invoice&Proxy<Invoice> createOne(array $attributes = [])
 * @phpstan-method static Invoice&Proxy<Invoice> find((object | array | mixed) $criteria)
 * @phpstan-method static Invoice&Proxy<Invoice> findOrCreate(array $attributes)
 * @phpstan-method static Invoice&Proxy<Invoice> first(string $sortedField = 'id')
 * @phpstan-method static Invoice&Proxy<Invoice> last(string $sortedField = 'id')
 * @phpstan-method static Invoice&Proxy<Invoice> random(array $attributes = [])
 * @phpstan-method static Invoice&Proxy<Invoice> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Invoice&Proxy<Invoice>> all()
 * @phpstan-method static list<Invoice&Proxy<Invoice>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Invoice&Proxy<Invoice>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Invoice&Proxy<Invoice>> findBy(array $attributes)
 * @phpstan-method static list<Invoice&Proxy<Invoice>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Invoice&Proxy<Invoice>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Invoice&Proxy<Invoice>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Invoice&Proxy<Invoice>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Invoice>
 */
final class InvoiceFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            // 'uuid' => Uuid::v7(),
            'due' => self::faker()->dateTime(),
            'paidDate' => self::faker()->dateTime(),
            'status' => self::faker()->word(),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'balance' => BigInteger::of(self::faker()->randomNumber()),
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
        return Invoice::class;
    }
}
