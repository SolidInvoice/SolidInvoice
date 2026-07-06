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

namespace SolidInvoice\ClientBundle\Test\Factory;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Client create((array | callable) $attributes = [])
 * @method static Client createOne(array $attributes = [])
 * @method static Client find((object | array | mixed) $criteria)
 * @method static Client findOrCreate(array $attributes)
 * @method static Client first(string $sortedField = 'id')
 * @method static Client last(string $sortedField = 'id')
 * @method static Client random(array $attributes = [])
 * @method static Client randomOrCreate(array $attributes = [])
 * @method static Client[] all()
 * @method static Client[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Client[] createSequence((iterable | callable) $sequence)
 * @method static Client[] findBy(array $attributes)
 * @method static Client[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Client[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Client> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Client> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<Client, ClientRepository> repository()
 *
 * @phpstan-method Client create((array | callable) $attributes = [])
 * @phpstan-method static Client createOne(array $attributes = [])
 * @phpstan-method static Client find((object | array | mixed) $criteria)
 * @phpstan-method static Client findOrCreate(array $attributes)
 * @phpstan-method static Client first(string $sortedField = 'id')
 * @phpstan-method static Client last(string $sortedField = 'id')
 * @phpstan-method static Client random(array $attributes = [])
 * @phpstan-method static Client randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Client> all()
 * @phpstan-method static list<Client> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Client> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Client> findBy(array $attributes)
 * @phpstan-method static list<Client> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Client> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Client> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Client> sequence((iterable | callable) $sequence)
 * @extends PersistentObjectFactory<Client>
 */
final class ClientFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->company(),
            'website' => 'https://' . self::faker()->domainName(),
            'status' => self::faker()->randomElement(ClientStatus::cases()),
            'currencyCode' => self::faker()->currencyCode(),
            'archived' => null,
            'created' => self::faker()->dateTime('2014-02-25 08:37:17'),
            'updated' => self::faker()->dateTime('2014-02-25 08:37:17'),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Client::class;
    }
}
