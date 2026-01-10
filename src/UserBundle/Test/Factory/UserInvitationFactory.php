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
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method UserInvitation|Proxy create((array | callable) $attributes = [])
 * @method static UserInvitation|Proxy createOne(array $attributes = [])
 * @method static UserInvitation|Proxy find((object | array | mixed) $criteria)
 * @method static UserInvitation|Proxy findOrCreate(array $attributes)
 * @method static UserInvitation|Proxy first(string $sortedField = 'id')
 * @method static UserInvitation|Proxy last(string $sortedField = 'id')
 * @method static UserInvitation|Proxy random(array $attributes = [])
 * @method static UserInvitation|Proxy randomOrCreate(array $attributes = [])
 * @method static UserInvitation[]|Proxy[] all()
 * @method static UserInvitation[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static UserInvitation[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static UserInvitation[]|Proxy[] findBy(array $attributes)
 * @method static UserInvitation[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static UserInvitation[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(UserInvitation | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(UserInvitation | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<UserInvitation, UserInvitationRepository> repository()
 *
 * @phpstan-method UserInvitation&Proxy<UserInvitation> create((array | callable) $attributes = [])
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> createOne(array $attributes = [])
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> find((object | array | mixed) $criteria)
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> findOrCreate(array $attributes)
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> first(string $sortedField = 'id')
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> last(string $sortedField = 'id')
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> random(array $attributes = [])
 * @phpstan-method static UserInvitation&Proxy<UserInvitation> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> all()
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> findBy(array $attributes)
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<UserInvitation&Proxy<UserInvitation>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<UserInvitation&Proxy<UserInvitation>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<UserInvitation&Proxy<UserInvitation>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<UserInvitation>
 */
final class UserInvitationFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->email(),
            'status' => UserInvitation::STATUS_PENDING,
            'company' => CompanyFactory::random(),
            'invitedBy' => UserFactory::random(),
        ];
    }

    public static function class(): string
    {
        return UserInvitation::class;
    }
}
