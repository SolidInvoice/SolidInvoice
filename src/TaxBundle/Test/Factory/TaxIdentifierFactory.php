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

namespace SolidInvoice\TaxBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method TaxIdentifier create((array | callable) $attributes = [])
 * @method static TaxIdentifier createOne(array $attributes = [])
 * @method static TaxIdentifier find((object | array | mixed) $criteria)
 * @method static TaxIdentifier findOrCreate(array $attributes)
 * @method static TaxIdentifier first(string $sortedField = 'id')
 * @method static TaxIdentifier last(string $sortedField = 'id')
 * @method static TaxIdentifier random(array $attributes = [])
 * @method static TaxIdentifier randomOrCreate(array $attributes = [])
 * @method static TaxIdentifier[] all()
 * @method static TaxIdentifier[] createMany(int $number, (array | callable) $attributes = [])
 * @method static TaxIdentifier[] createSequence((iterable | callable) $sequence)
 * @method static TaxIdentifier[] findBy(array $attributes)
 * @method static TaxIdentifier[] randomRange(int $min, int $max, array $attributes = [])
 * @method static TaxIdentifier[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<TaxIdentifier> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<TaxIdentifier> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<TaxIdentifier, TaxIdentifierRepository> repository()
 *
 * @phpstan-method TaxIdentifier create((array | callable) $attributes = [])
 * @phpstan-method static TaxIdentifier createOne(array $attributes = [])
 * @extends PersistentObjectFactory<TaxIdentifier>
 */
final class TaxIdentifierFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->randomElement(['VAT', 'GSTIN', 'TIN']),
            'value' => strtoupper(self::faker()->bothify('??#########')),
            'primary' => false,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return TaxIdentifier::class;
    }
}
