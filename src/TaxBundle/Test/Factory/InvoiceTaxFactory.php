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
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Repository\InvoiceTaxRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method InvoiceTax create((array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceTax createOne(array<string, mixed> $attributes = [])
 * @method static InvoiceTax find((object | array<string, mixed> | mixed) $criteria)
 * @method static InvoiceTax findOrCreate(array<string, mixed> $attributes)
 * @method static InvoiceTax first(string $sortedField = 'id')
 * @method static InvoiceTax last(string $sortedField = 'id')
 * @method static InvoiceTax random(array<string, mixed> $attributes = [])
 * @method static InvoiceTax randomOrCreate(array<string, mixed> $attributes = [])
 * @method static InvoiceTax[] all()
 * @method static InvoiceTax[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceTax[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static InvoiceTax[] findBy(array<string, mixed> $attributes)
 * @method static InvoiceTax[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static InvoiceTax[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<InvoiceTax, InvoiceTaxFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<InvoiceTax, InvoiceTaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<InvoiceTax, InvoiceTaxRepository> repository()
 *
 * @phpstan-method InvoiceTax create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static InvoiceTax createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceTax find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static InvoiceTax findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static InvoiceTax first(string $sortedField = 'id')
 * @phpstan-method static InvoiceTax last(string $sortedField = 'id')
 * @phpstan-method static InvoiceTax random(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceTax randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceTax> all()
 * @phpstan-method static list<InvoiceTax> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<InvoiceTax> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<InvoiceTax> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<InvoiceTax> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceTax> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<InvoiceTax, InvoiceTaxFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<InvoiceTax, InvoiceTaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<InvoiceTax>
 */
final class InvoiceTaxFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'nameSnapshot' => self::faker()->randomElement(['Withholding', 'Reverse Charge', 'Surcharge']),
            'rateSnapshot' => self::faker()->randomFloat(4, 0, 25),
            'categorySnapshot' => TaxCategory::Standard,
            'direction' => self::faker()->randomElement(TaxDirection::cases()),
            'sequence' => 0,
            'amount' => 0,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return InvoiceTax::class;
    }
}
