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

use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Credit create(array<string, mixed>|callable $attributes = [])
 * @method static Credit createOne(array<string, mixed> $attributes = [])
 * @method static Credit find(object|array<string, mixed>|mixed $criteria)
 * @method static Credit findOrCreate(array<string, mixed> $attributes)
 * @method static Credit first(string $sortedField = 'id')
 * @method static Credit last(string $sortedField = 'id')
 * @method static Credit random(array<string, mixed> $attributes = [])
 * @method static Credit randomOrCreate(array<string, mixed> $attributes = [])
 * @method static Credit[] all()
 * @method static Credit[] createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @method static Credit[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static Credit[] findBy(array<string, mixed> $attributes)
 * @method static Credit[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static Credit[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<Credit, CreditFactory> many(int $min, int|null $max = null)
 * @method FactoryCollection<Credit, CreditFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<Credit, CreditRepository> repository()
 *
 * @phpstan-method Credit create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static Credit createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static Credit find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static Credit findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static Credit first(string $sortedField = 'id')
 * @phpstan-method static Credit last(string $sortedField = 'id')
 * @phpstan-method static Credit random(array<string, mixed> $attributes = [])
 * @phpstan-method static Credit randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<Credit> all()
 * @phpstan-method static list<Credit> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<Credit> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<Credit> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<Credit> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<Credit> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<Credit, CreditFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Credit, CreditFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<Credit>
 */
final class CreditFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'value' => new Money(self::faker()->randomNumber(), new Currency(self::faker()->currencyCode())),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Credit::class;
    }
}
