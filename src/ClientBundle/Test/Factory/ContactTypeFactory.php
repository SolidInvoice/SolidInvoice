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

use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Repository\ContactTypeRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<ContactType>
 *
 * @method static ContactType|Proxy createOne(array $attributes = [])
 * @method static ContactType[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ContactType[]|Proxy[] createSequence(array|callable $sequence)
 * @method static ContactType|Proxy find(object|array|mixed $criteria)
 * @method static ContactType|Proxy findOrCreate(array $attributes)
 * @method static ContactType|Proxy first(string $sortedField = 'id')
 * @method static ContactType|Proxy last(string $sortedField = 'id')
 * @method static ContactType|Proxy random(array $attributes = [])
 * @method static ContactType|Proxy randomOrCreate(array $attributes = [])
 * @method static ContactType[]|Proxy[] all()
 * @method static ContactType[]|Proxy[] findBy(array $attributes)
 * @method static ContactType[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static ContactType[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ContactTypeRepository|RepositoryProxy repository()
 * @method ContactType|Proxy create(array|callable $attributes = [])
 */
final class ContactTypeFactory extends ModelFactory
{
    public const TYPES = [
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->randomElement(self::TYPES),
            'type' => 'text',
            'company' => CompanyFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return ContactType::class;
    }
}
