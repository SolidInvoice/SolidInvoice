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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<PaymentMethod>
 *
 * @method static PaymentMethod|Proxy createOne(array $attributes = [])
 * @method static PaymentMethod[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static PaymentMethod[]|Proxy[] createSequence(array|callable $sequence)
 * @method static PaymentMethod|Proxy find(object|array|mixed $criteria)
 * @method static PaymentMethod|Proxy findOrCreate(array $attributes)
 * @method static PaymentMethod|Proxy first(string $sortedField = 'id')
 * @method static PaymentMethod|Proxy last(string $sortedField = 'id')
 * @method static PaymentMethod|Proxy random(array $attributes = [])
 * @method static PaymentMethod|Proxy randomOrCreate(array $attributes = [])
 * @method static PaymentMethod[]|Proxy[] all()
 * @method static PaymentMethod[]|Proxy[] findBy(array $attributes)
 * @method static PaymentMethod[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static PaymentMethod[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static PaymentMethodRepository|RepositoryProxy repository()
 * @method PaymentMethod|Proxy create(array|callable $attributes = [])
 */
final class PaymentMethodFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
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

    protected static function getClass(): string
    {
        return PaymentMethod::class;
    }
}
