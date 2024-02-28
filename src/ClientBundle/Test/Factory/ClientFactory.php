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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Client>
 *
 * @method static Client|Proxy createOne(array $attributes = [])
 * @method static Client[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Client[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Client|Proxy find(object|array|mixed $criteria)
 * @method static Client|Proxy findOrCreate(array $attributes)
 * @method static Client|Proxy first(string $sortedField = 'id')
 * @method static Client|Proxy last(string $sortedField = 'id')
 * @method static Client|Proxy random(array $attributes = [])
 * @method static Client|Proxy randomOrCreate(array $attributes = [])
 * @method static Client[]|Proxy[] all()
 * @method static Client[]|Proxy[] findBy(array $attributes)
 * @method static Client[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Client[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ClientRepository|RepositoryProxy repository()
 * @method Client|Proxy create(array|callable $attributes = [])
 */
final class ClientFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->domainName(),
            'website' => self::faker()->boolean() ? self::faker()->url() : '',
            'status' => 'active',
            'currencyCode' => self::faker()->currencyCode(),
            'vatNumber' => self::faker()->word(),
            'archived' => self::faker()->boolean(10) ? true : null,
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'company' => CompanyFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return Client::class;
    }
}
