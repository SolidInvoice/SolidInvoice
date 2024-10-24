<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Test\Factory;

use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Payment|Proxy create((array | callable) $attributes = [])
 * @method static Payment|Proxy createOne(array $attributes = [])
 * @method static Payment|Proxy find((object | array | mixed) $criteria)
 * @method static Payment|Proxy findOrCreate(array $attributes)
 * @method static Payment|Proxy first(string $sortedField = 'id')
 * @method static Payment|Proxy last(string $sortedField = 'id')
 * @method static Payment|Proxy random(array $attributes = [])
 * @method static Payment|Proxy randomOrCreate(array $attributes = [])
 * @method static Payment[]|Proxy[] all()
 * @method static Payment[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Payment[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static Payment[]|Proxy[] findBy(array $attributes)
 * @method static Payment[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Payment[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Payment | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Payment | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Payment, PaymentRepository> repository()
 *
 * @phpstan-method Payment&Proxy<Payment> create((array | callable) $attributes = [])
 * @phpstan-method static Payment&Proxy<Payment> createOne(array $attributes = [])
 * @phpstan-method static Payment&Proxy<Payment> find((object | array | mixed) $criteria)
 * @phpstan-method static Payment&Proxy<Payment> findOrCreate(array $attributes)
 * @phpstan-method static Payment&Proxy<Payment> first(string $sortedField = 'id')
 * @phpstan-method static Payment&Proxy<Payment> last(string $sortedField = 'id')
 * @phpstan-method static Payment&Proxy<Payment> random(array $attributes = [])
 * @phpstan-method static Payment&Proxy<Payment> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Payment&Proxy<Payment>> all()
 * @phpstan-method static list<Payment&Proxy<Payment>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Payment&Proxy<Payment>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Payment&Proxy<Payment>> findBy(array $attributes)
 * @phpstan-method static list<Payment&Proxy<Payment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Payment&Proxy<Payment>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Payment&Proxy<Payment>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Payment&Proxy<Payment>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Payment>
 */
final class PaymentFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'number' => self::faker()->text(),
            'description' => self::faker()->text(),
            'clientEmail' => self::faker()->text(),
            'totalAmount' => self::faker()->randomNumber(),
            'currencyCode' => self::faker()->currencyCode(),
            'details' => [],
            'status' => self::faker()->word(),
            'message' => self::faker()->text(),
            'completed' => self::faker()->dateTime(),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
        ];
    }

    public static function class(): string
    {
        return Payment::class;
    }
}
