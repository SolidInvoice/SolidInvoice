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

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ContactRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Contact|Proxy create(array|callable $attributes = [])
 * @method static Contact|Proxy createOne(array $attributes = [])
 * @method static Contact|Proxy find(object|array|mixed $criteria)
 * @method static Contact|Proxy findOrCreate(array $attributes)
 * @method static Contact|Proxy first(string $sortedField = 'id')
 * @method static Contact|Proxy last(string $sortedField = 'id')
 * @method static Contact|Proxy random(array $attributes = [])
 * @method static Contact|Proxy randomOrCreate(array $attributes = [])
 * @method static Contact[]|Proxy[] all()
 * @method static Contact[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Contact[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Contact[]|Proxy[] findBy(array $attributes)
 * @method static Contact[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Contact[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Contact|Proxy> many(int $min, int|null $max = null)
 * @method FactoryCollection<Contact|Proxy> sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<Contact, ContactRepository> repository()
 *
 * @phpstan-method Contact&Proxy<Contact> create(array|callable $attributes = [])
 * @phpstan-method static Contact&Proxy<Contact> createOne(array $attributes = [])
 * @phpstan-method static Contact&Proxy<Contact> find(object|array|mixed $criteria)
 * @phpstan-method static Contact&Proxy<Contact> findOrCreate(array $attributes)
 * @phpstan-method static Contact&Proxy<Contact> first(string $sortedField = 'id')
 * @phpstan-method static Contact&Proxy<Contact> last(string $sortedField = 'id')
 * @phpstan-method static Contact&Proxy<Contact> random(array $attributes = [])
 * @phpstan-method static Contact&Proxy<Contact> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Contact&Proxy<Contact>> all()
 * @phpstan-method static list<Contact&Proxy<Contact>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Contact&Proxy<Contact>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Contact&Proxy<Contact>> findBy(array $attributes)
 * @phpstan-method static list<Contact&Proxy<Contact>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Contact&Proxy<Contact>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Contact&Proxy<Contact>> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Contact&Proxy<Contact>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<Contact>
 */
final class ContactFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->email(),
            'company' => CompanyFactory::new(),
        ];
    }

    public static function class(): string
    {
        return Contact::class;
    }
}
