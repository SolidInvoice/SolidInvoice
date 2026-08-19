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

namespace SolidInvoice\UserBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method User create((array<string, mixed> | callable) $attributes = [])
 * @method static User createOne(array<string, mixed> $attributes = [])
 * @method static User find((object | array<string, mixed> | mixed) $criteria)
 * @method static User findOrCreate(array<string, mixed> $attributes)
 * @method static User first(string $sortedField = 'id')
 * @method static User last(string $sortedField = 'id')
 * @method static User random(array<string, mixed> $attributes = [])
 * @method static User randomOrCreate(array<string, mixed> $attributes = [])
 * @method static User[] all()
 * @method static User[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static User[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static User[] findBy(array<string, mixed> $attributes)
 * @method static User[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static User[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<User, UserFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<User, UserFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<User, UserRepository> repository()
 *
 * @phpstan-method User create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static User createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static User find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static User findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static User first(string $sortedField = 'id')
 * @phpstan-method static User last(string $sortedField = 'id')
 * @phpstan-method static User random(array<string, mixed> $attributes = [])
 * @phpstan-method static User randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<User> all()
 * @phpstan-method static list<User> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<User> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<User> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<User> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<User> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<User, UserFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<User, UserFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $email = self::faker()->email();
        return [
            'email' => $email,
            'enabled' => true,
            'password' => '$argon2id$v=19$m=65536,t=4,p=1$pLFF3D2gnvDmxMuuqH4BrA$3vKfv0cw+6EaNspq9btVAYc+jCOqrmWRstInB2fRPeQ',
            'verified' => true,
            'roles' => [],
            'companies' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return User::class;
    }
}
