<?php

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
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Client|Proxy create((array | callable) $attributes = [])
 * @method static Client|Proxy createOne(array $attributes = [])
 * @method static Client|Proxy find((object | array | mixed) $criteria)
 * @method static Client|Proxy findOrCreate(array $attributes)
 * @method static Client|Proxy first(string $sortedField = 'id')
 * @method static Client|Proxy last(string $sortedField = 'id')
 * @method static Client|Proxy random(array $attributes = [])
 * @method static Client|Proxy randomOrCreate(array $attributes = [])
 * @method static Client[]|Proxy[] all()
 * @method static Client[]|Proxy[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Client[]|Proxy[] createSequence((iterable | callable) $sequence)
 * @method static Client[]|Proxy[] findBy(array $attributes)
 * @method static Client[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Client[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Client | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Client | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Client, ClientRepository> repository()
 *
 * @phpstan-method Client&Proxy<Client> create((array | callable) $attributes = [])
 * @phpstan-method static Client&Proxy<Client> createOne(array $attributes = [])
 * @phpstan-method static Client&Proxy<Client> find((object | array | mixed) $criteria)
 * @phpstan-method static Client&Proxy<Client> findOrCreate(array $attributes)
 * @phpstan-method static Client&Proxy<Client> first(string $sortedField = 'id')
 * @phpstan-method static Client&Proxy<Client> last(string $sortedField = 'id')
 * @phpstan-method static Client&Proxy<Client> random(array $attributes = [])
 * @phpstan-method static Client&Proxy<Client> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Client&Proxy<Client>> all()
 * @phpstan-method static list<Client&Proxy<Client>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Client&Proxy<Client>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Client&Proxy<Client>> findBy(array $attributes)
 * @phpstan-method static list<Client&Proxy<Client>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Client&Proxy<Client>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Client&Proxy<Client>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Client&Proxy<Client>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Client>
 */
final class ClientFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'website' => 'https://' . self::faker()->domainName(),
            'status' => self::faker()->word(),
            'currencyCode' => self::faker()->currencyCode(),
            'vatNumber' => self::faker()->word(),
            'archived' => null,
            'created' => self::faker()->dateTime('2014-02-25 08:37:17'),
            'updated' => self::faker()->dateTime('2014-02-25 08:37:17'),
            'company' => CompanyFactory::new(),
        ];
    }

    public static function class(): string
    {
        return Client::class;
    }
}
