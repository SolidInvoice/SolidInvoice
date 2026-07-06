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
 * @method Credit create(array|callable $attributes = [])
 * @method static Credit createOne(array $attributes = [])
 * @method static Credit find(object|array|mixed $criteria)
 * @method static Credit findOrCreate(array $attributes)
 * @method static Credit first(string $sortedField = 'id')
 * @method static Credit last(string $sortedField = 'id')
 * @method static Credit random(array $attributes = [])
 * @method static Credit randomOrCreate(array $attributes = [])
 * @method static Credit[] all()
 * @method static Credit[] createMany(int $number, array|callable $attributes = [])
 * @method static Credit[] createSequence(iterable|callable $sequence)
 * @method static Credit[] findBy(array $attributes)
 * @method static Credit[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Credit[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Credit> many(int $min, int|null $max = null)
 * @method FactoryCollection<Credit> sequence(iterable|callable $sequence)
 * @method static RepositoryDecorator<Credit, CreditRepository> repository()
 *
 * @phpstan-method Credit create(array|callable $attributes = [])
 * @phpstan-method static Credit createOne(array $attributes = [])
 * @phpstan-method static Credit find(object|array|mixed $criteria)
 * @phpstan-method static Credit findOrCreate(array $attributes)
 * @phpstan-method static Credit first(string $sortedField = 'id')
 * @phpstan-method static Credit last(string $sortedField = 'id')
 * @phpstan-method static Credit random(array $attributes = [])
 * @phpstan-method static Credit randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Credit> all()
 * @phpstan-method static list<Credit> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Credit> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Credit> findBy(array $attributes)
 * @phpstan-method static list<Credit> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Credit> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Credit> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<Credit> sequence(iterable|callable $sequence)
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
