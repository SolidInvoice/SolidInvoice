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

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ContactRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Contact create(array|callable $attributes = [])
 * @method static Contact createOne(array $attributes = [])
 * @method static Contact find(object|array|mixed $criteria)
 * @method static Contact findOrCreate(array $attributes)
 * @method static Contact first(string $sortedField = 'id')
 * @method static Contact last(string $sortedField = 'id')
 * @method static Contact random(array $attributes = [])
 * @method static Contact randomOrCreate(array $attributes = [])
 * @method static Contact[] all()
 * @method static Contact[] createMany(int $number, array|callable $attributes = [])
 * @method static Contact[] createSequence(iterable|callable $sequence)
 * @method static Contact[] findBy(array $attributes)
 * @method static Contact[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Contact[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Contact> many(int $min, int|null $max = null)
 * @method FactoryCollection<Contact> sequence(iterable|callable $sequence)
 * @method static RepositoryDecorator<Contact, ContactRepository> repository()
 *
 * @phpstan-method Contact create(array|callable $attributes = [])
 * @phpstan-method static Contact createOne(array $attributes = [])
 * @phpstan-method static Contact find(object|array|mixed $criteria)
 * @phpstan-method static Contact findOrCreate(array $attributes)
 * @phpstan-method static Contact first(string $sortedField = 'id')
 * @phpstan-method static Contact last(string $sortedField = 'id')
 * @phpstan-method static Contact random(array $attributes = [])
 * @phpstan-method static Contact randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Contact> all()
 * @phpstan-method static list<Contact> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Contact> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Contact> findBy(array $attributes)
 * @phpstan-method static list<Contact> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Contact> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Contact> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Contact> sequence(iterable|callable $sequence)
 * @extends PersistentObjectFactory<Contact>
 */
final class ContactFactory extends PersistentObjectFactory
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
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Contact::class;
    }
}
