<?php

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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method User|Proxy create((array | callable) $attributes = [])
 * @method static User|Proxy createOne(array $attributes = [])
 * @method static User|Proxy find((object | array | mixed) $criteria)
 * @method static User|Proxy findOrCreate(array $attributes)
 * @method static User|Proxy first(string $sortedField = 'id')
 * @method static User|Proxy last(string $sortedField = 'id')
 * @method static User|Proxy random(array $attributes = [])
 * @method static User|Proxy randomOrCreate(array $attributes = [])
 * @method static User[]|Proxy[] all()
 * @method static User[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static User[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static User[]|Proxy[] findBy(array $attributes)
 * @method static User[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static User[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(User | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(User | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<User, UserRepository> repository()
 *
 * @phpstan-method User&Proxy<User> create((array | callable) $attributes = [])
 * @phpstan-method static User&Proxy<User> createOne(array $attributes = [])
 * @phpstan-method static User&Proxy<User> find((object | array | mixed) $criteria)
 * @phpstan-method static User&Proxy<User> findOrCreate(array $attributes)
 * @phpstan-method static User&Proxy<User> first(string $sortedField = 'id')
 * @phpstan-method static User&Proxy<User> last(string $sortedField = 'id')
 * @phpstan-method static User&Proxy<User> random(array $attributes = [])
 * @phpstan-method static User&Proxy<User> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<User&Proxy<User>> all()
 * @phpstan-method static list<User&Proxy<User>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<User&Proxy<User>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<User&Proxy<User>> findBy(array $attributes)
 * @phpstan-method static list<User&Proxy<User>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<User&Proxy<User>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<User&Proxy<User>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<User&Proxy<User>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    private PasswordHasherInterface $passwordHasher;

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
            'enabled' => self::faker()->boolean(),
            'password' => $this->passwordHasher->hash(self::faker()->password()),
            'roles' => [],
            'companies' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return User::class;
    }
}
