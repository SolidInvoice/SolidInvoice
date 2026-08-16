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

namespace SolidInvoice\NotificationBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method UserNotification create((array<string, mixed> | callable) $attributes = [])
 * @method static UserNotification createOne(array<string, mixed> $attributes = [])
 * @method static UserNotification find((object | array<string, mixed> | mixed) $criteria)
 * @method static UserNotification findOrCreate(array<string, mixed> $attributes)
 * @method static UserNotification first(string $sortedField = 'id')
 * @method static UserNotification last(string $sortedField = 'id')
 * @method static UserNotification random(array<string, mixed> $attributes = [])
 * @method static UserNotification randomOrCreate(array<string, mixed> $attributes = [])
 * @method static UserNotification[] all()
 * @method static UserNotification[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static UserNotification[] createSequence(iterable<array<string, mixed>> | callable $sequence)
 * @method static UserNotification[] findBy(array<string, mixed> $attributes)
 * @method static UserNotification[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static UserNotification[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<UserNotification, UserNotificationFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<UserNotification, UserNotificationFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<UserNotification, UserNotificationRepository> repository()
 *
 * @phpstan-method UserNotification create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static UserNotification createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static UserNotification find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static UserNotification findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static UserNotification first(string $sortedField = 'id')
 * @phpstan-method static UserNotification last(string $sortedField = 'id')
 * @phpstan-method static UserNotification random(array<string, mixed> $attributes = [])
 * @phpstan-method static UserNotification randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<UserNotification> all()
 * @phpstan-method static list<UserNotification> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<UserNotification> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<UserNotification> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<UserNotification> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<UserNotification> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<UserNotification, UserNotificationFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<UserNotification, UserNotificationFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<UserNotification>
 */
final class UserNotificationFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'event' => self::faker()->word(),
            'email' => self::faker()->boolean(),
            'user' => UserFactory::random(),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return UserNotification::class;
    }
}
