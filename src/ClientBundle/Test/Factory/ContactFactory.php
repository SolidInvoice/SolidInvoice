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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Contact>
 *
 * @method static Contact|Proxy createOne(array $attributes = [])
 * @method static Contact[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Contact[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Contact|Proxy find(object|array|mixed $criteria)
 * @method static Contact|Proxy findOrCreate(array $attributes)
 * @method static Contact|Proxy first(string $sortedField = 'id')
 * @method static Contact|Proxy last(string $sortedField = 'id')
 * @method static Contact|Proxy random(array $attributes = [])
 * @method static Contact|Proxy randomOrCreate(array $attributes = [])
 * @method static Contact[]|Proxy[] all()
 * @method static Contact[]|Proxy[] findBy(array $attributes)
 * @method static Contact[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Contact[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ContactRepository|RepositoryProxy repository()
 * @method Contact|Proxy create(array|callable $attributes = [])
 */
final class ContactFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => self::faker()->email(),
            'company' => CompanyFactory::new(),
            //'client' => ClientFactory::random(),
            //'additionalContactDetails' => AdditionalContactDetailFactory::new()->many(0, 5),
        ];
    }

    protected static function getClass(): string
    {
        return Contact::class;
    }
}
