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
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\LineTaxRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method LineTax create((array<string, mixed> | callable) $attributes = [])
 * @method static LineTax createOne(array<string, mixed> $attributes = [])
 * @method static LineTax find((object | array<string, mixed> | mixed) $criteria)
 * @method static LineTax findOrCreate(array<string, mixed> $attributes)
 * @method static LineTax first(string $sortedField = 'id')
 * @method static LineTax last(string $sortedField = 'id')
 * @method static LineTax random(array<string, mixed> $attributes = [])
 * @method static LineTax randomOrCreate(array<string, mixed> $attributes = [])
 * @method static LineTax[] all()
 * @method static LineTax[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static LineTax[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static LineTax[] findBy(array<string, mixed> $attributes)
 * @method static LineTax[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static LineTax[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<LineTax, LineTaxFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<LineTax, LineTaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<LineTax, LineTaxRepository> repository()
 *
 * @phpstan-method LineTax create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static LineTax createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static LineTax find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static LineTax findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static LineTax first(string $sortedField = 'id')
 * @phpstan-method static LineTax last(string $sortedField = 'id')
 * @phpstan-method static LineTax random(array<string, mixed> $attributes = [])
 * @phpstan-method static LineTax randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<LineTax> all()
 * @phpstan-method static list<LineTax> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<LineTax> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<LineTax> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<LineTax> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<LineTax> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<LineTax, LineTaxFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<LineTax, LineTaxFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<LineTax>
 */
final class LineTaxFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'nameSnapshot' => self::faker()->randomElement(['VAT', 'GST', 'Sales Tax']),
            'rateSnapshot' => self::faker()->randomFloat(4, 0, 25),
            'categorySnapshot' => TaxCategory::Standard,
            'typeSnapshot' => self::faker()->randomElement(TaxType::cases()),
            'compound' => false,
            'sequence' => 0,
            'amount' => 0,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return LineTax::class;
    }
}
