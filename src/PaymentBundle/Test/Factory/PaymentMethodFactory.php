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

use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method PaymentMethod|Proxy create(array|callable $attributes = [])
 * @method static PaymentMethod|Proxy createOne(array $attributes = [])
 * @method static PaymentMethod|Proxy find(object|array|mixed $criteria)
 * @method static PaymentMethod|Proxy findOrCreate(array $attributes)
 * @method static PaymentMethod|Proxy first(string $sortedField = 'id')
 * @method static PaymentMethod|Proxy last(string $sortedField = 'id')
 * @method static PaymentMethod|Proxy random(array $attributes = [])
 * @method static PaymentMethod|Proxy randomOrCreate(array $attributes = [])
 * @method static PaymentMethod[]|Proxy[] all()
 * @method static PaymentMethod[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static PaymentMethod[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static PaymentMethod[]|Proxy[] findBy(array $attributes)
 * @method static PaymentMethod[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static PaymentMethod[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<PaymentMethod|Proxy> many(int $min, int|null $max = null)
 * @method FactoryCollection<PaymentMethod|Proxy> sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<PaymentMethod, PaymentMethodRepository> repository()
 *
 * @phpstan-method PaymentMethod&Proxy<PaymentMethod> create(array|callable $attributes = [])
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> createOne(array $attributes = [])
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> find(object|array|mixed $criteria)
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> findOrCreate(array $attributes)
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> first(string $sortedField = 'id')
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> last(string $sortedField = 'id')
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> random(array $attributes = [])
 * @phpstan-method static PaymentMethod&Proxy<PaymentMethod> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> all()
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> findBy(array $attributes)
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<PaymentMethod&Proxy<PaymentMethod>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<PaymentMethod&Proxy<PaymentMethod>> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<PaymentMethod&Proxy<PaymentMethod>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<PaymentMethod>
 */
final class PaymentMethodFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(),
            'gatewayName' => self::faker()->name(),
            'factoryName' => self::faker()->name(),
            'config' => [],
            'internal' => self::faker()->boolean(),
            'enabled' => self::faker()->boolean(),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
        ];
    }

    public static function class(): string
    {
        return PaymentMethod::class;
    }
}
