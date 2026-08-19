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

namespace SolidInvoice\PaymentBundle\Test\Factory;

use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method PaymentMethod create(array<string, mixed>|callable $attributes = [])
 * @method static PaymentMethod createOne(array<string, mixed> $attributes = [])
 * @method static PaymentMethod find(object|array<string, mixed>|mixed $criteria)
 * @method static PaymentMethod findOrCreate(array<string, mixed> $attributes)
 * @method static PaymentMethod first(string $sortedField = 'id')
 * @method static PaymentMethod last(string $sortedField = 'id')
 * @method static PaymentMethod random(array<string, mixed> $attributes = [])
 * @method static PaymentMethod randomOrCreate(array<string, mixed> $attributes = [])
 * @method static PaymentMethod[] all()
 * @method static PaymentMethod[] createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @method static PaymentMethod[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static PaymentMethod[] findBy(array<string, mixed> $attributes)
 * @method static PaymentMethod[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static PaymentMethod[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<PaymentMethod, PaymentMethodFactory> many(int $min, int|null $max = null)
 * @method FactoryCollection<PaymentMethod, PaymentMethodFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<PaymentMethod, PaymentMethodRepository> repository()
 *
 * @phpstan-method PaymentMethod create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static PaymentMethod createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static PaymentMethod find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static PaymentMethod findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static PaymentMethod first(string $sortedField = 'id')
 * @phpstan-method static PaymentMethod last(string $sortedField = 'id')
 * @phpstan-method static PaymentMethod random(array<string, mixed> $attributes = [])
 * @phpstan-method static PaymentMethod randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<PaymentMethod> all()
 * @phpstan-method static list<PaymentMethod> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<PaymentMethod> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<PaymentMethod> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<PaymentMethod> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<PaymentMethod> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<PaymentMethod, PaymentMethodFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<PaymentMethod, PaymentMethodFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<PaymentMethod>
 */
final class PaymentMethodFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
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
