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

use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Payment create((array<string, mixed> | callable) $attributes = [])
 * @method static Payment createOne(array<string, mixed> $attributes = [])
 * @method static Payment find((object | array<string, mixed> | mixed) $criteria)
 * @method static Payment findOrCreate(array<string, mixed> $attributes)
 * @method static Payment first(string $sortedField = 'id')
 * @method static Payment last(string $sortedField = 'id')
 * @method static Payment random(array<string, mixed> $attributes = [])
 * @method static Payment randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Payment[] all()
 * @method static Payment[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static Payment[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Payment[] findBy(array<string, mixed> $attributes)
 * @method static Payment[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Payment[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Payment, PaymentFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Payment, PaymentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Payment, PaymentRepository> repository()
 *
 * @phpstan-method Payment create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Payment createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Payment find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Payment findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Payment first(string $sortedField = 'id')
 * @phpstan-method static Payment last(string $sortedField = 'id')
 * @phpstan-method static Payment random(array<string, mixed> $attributes = [])
 * @phpstan-method static Payment randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Payment> all()
 * @phpstan-method static list<Payment> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Payment> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Payment> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Payment> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Payment> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Payment, PaymentFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Payment, PaymentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<Payment>
 */
final class PaymentFactory extends PersistentObjectFactory
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
            'status' => self::faker()->randomElement(PaymentStatus::cases()),
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
