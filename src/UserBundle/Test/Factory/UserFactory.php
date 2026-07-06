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
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method User create((array | callable) $attributes = [])
 * @method static User createOne(array $attributes = [])
 * @method static User find((object | array | mixed) $criteria)
 * @method static User findOrCreate(array $attributes)
 * @method static User first(string $sortedField = 'id')
 * @method static User last(string $sortedField = 'id')
 * @method static User random(array $attributes = [])
 * @method static User randomOrCreate(array $attributes = [])
 * @method static User[] all()
 * @method static User[] createMany(int $number, (array | callable) $attributes = [])
 * @method static User[] createSequence((iterable | callable) $sequence)
 * @method static User[] findBy(array $attributes)
 * @method static User[] randomRange(int $min, int $max, array $attributes = [])
 * @method static User[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<User> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<User> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<User, UserRepository> repository()
 *
 * @phpstan-method User create((array | callable) $attributes = [])
 * @phpstan-method static User createOne(array $attributes = [])
 * @phpstan-method static User find((object | array | mixed) $criteria)
 * @phpstan-method static User findOrCreate(array $attributes)
 * @phpstan-method static User first(string $sortedField = 'id')
 * @phpstan-method static User last(string $sortedField = 'id')
 * @phpstan-method static User random(array $attributes = [])
 * @phpstan-method static User randomOrCreate(array $attributes = [])
 * @phpstan-method static list<User> all()
 * @phpstan-method static list<User> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<User> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<User> findBy(array $attributes)
 * @phpstan-method static list<User> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<User> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<User> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<User> sequence((iterable | callable) $sequence)
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    private readonly PasswordHasherInterface $passwordHasher;

    public function __construct()
    {
        parent::__construct();

        $this->passwordHasher = new NativePasswordHasher();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        $email = self::faker()->email();
        return [
            'email' => $email,
            'enabled' => true,
            'password' => $this->passwordHasher->hash(self::faker()->password()),
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
