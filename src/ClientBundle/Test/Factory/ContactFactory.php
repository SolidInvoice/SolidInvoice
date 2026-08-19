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
 * @method Contact create(array<string, mixed>|callable $attributes = [])
 * @method static Contact createOne(array<string, mixed> $attributes = [])
 * @method static Contact find(object|array<string, mixed>|mixed $criteria)
 * @method static Contact findOrCreate(array<string, mixed> $attributes)
 * @method static Contact first(string $sortedField = 'id')
 * @method static Contact last(string $sortedField = 'id')
 * @method static Contact random(array<string, mixed> $attributes = [])
 * @method static Contact randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Contact[] all()
 * @method static Contact[] createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @method static Contact[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Contact[] findBy(array<string, mixed> $attributes)
 * @method static Contact[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Contact[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Contact, ContactFactory> many(int $min, int|null $max = null)
 * @method FactoryCollection<Contact, ContactFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Contact, ContactRepository> repository()
 *
 * @phpstan-method Contact create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Contact createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Contact find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Contact findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Contact first(string $sortedField = 'id')
 * @phpstan-method static Contact last(string $sortedField = 'id')
 * @phpstan-method static Contact random(array<string, mixed> $attributes = [])
 * @phpstan-method static Contact randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Contact> all()
 * @phpstan-method static list<Contact> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Contact> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Contact> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Contact> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Contact> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Contact, ContactFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Contact, ContactFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
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
