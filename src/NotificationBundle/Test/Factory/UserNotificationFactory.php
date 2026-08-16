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
 * @method UserNotification create((array | callable) $attributes = [])
 * @method static UserNotification createOne(array $attributes = [])
 * @method static UserNotification find((object | array | mixed) $criteria)
 * @method static UserNotification findOrCreate(array $attributes)
 * @method static UserNotification first(string $sortedField = 'id')
 * @method static UserNotification last(string $sortedField = 'id')
 * @method static UserNotification random(array $attributes = [])
 * @method static UserNotification randomOrCreate(array $attributes = [])
 * @method static UserNotification[] all()
 * @method static UserNotification[] createMany(int $number, (array | callable) $attributes = [])
 * @method static UserNotification[] createSequence((iterable | callable) $sequence)
 * @method static UserNotification[] findBy(array $attributes)
 * @method static UserNotification[] randomRange(int $min, int $max, array $attributes = [])
 * @method static UserNotification[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<UserNotification> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<UserNotification> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<UserNotification, UserNotificationRepository> repository()
 *
 * @phpstan-method UserNotification create((array | callable) $attributes = [])
 * @phpstan-method static UserNotification createOne(array $attributes = [])
 * @phpstan-method static UserNotification find((object | array | mixed) $criteria)
 * @phpstan-method static UserNotification findOrCreate(array $attributes)
 * @phpstan-method static UserNotification first(string $sortedField = 'id')
 * @phpstan-method static UserNotification last(string $sortedField = 'id')
 * @phpstan-method static UserNotification random(array $attributes = [])
 * @phpstan-method static UserNotification randomOrCreate(array $attributes = [])
 * @phpstan-method static list<UserNotification> all()
 * @phpstan-method static list<UserNotification> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<UserNotification> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<UserNotification> findBy(array $attributes)
 * @phpstan-method static list<UserNotification> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<UserNotification> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<UserNotification> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<UserNotification> sequence((iterable|callable) $sequence)
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
