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

namespace SolidInvoice\QuoteBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Quote create((array | callable) $attributes = [])
 * @method static Quote createOne(array $attributes = [])
 * @method static Quote find((object | array | mixed) $criteria)
 * @method static Quote findOrCreate(array $attributes)
 * @method static Quote first(string $sortedField = 'id')
 * @method static Quote last(string $sortedField = 'id')
 * @method static Quote random(array $attributes = [])
 * @method static Quote randomOrCreate(array $attributes = [])
 * @method static Quote[] all()
 * @method static Quote[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Quote[] createSequence((iterable | callable) $sequence)
 * @method static Quote[] findBy(array $attributes)
 * @method static Quote[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Quote[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Quote> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Quote> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<Quote, QuoteRepository> repository()
 *
 * @phpstan-method Quote create((array | callable) $attributes = [])
 * @phpstan-method static Quote createOne(array $attributes = [])
 * @phpstan-method static Quote find((object | array | mixed) $criteria)
 * @phpstan-method static Quote findOrCreate(array $attributes)
 * @phpstan-method static Quote first(string $sortedField = 'id')
 * @phpstan-method static Quote last(string $sortedField = 'id')
 * @phpstan-method static Quote random(array $attributes = [])
 * @phpstan-method static Quote randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Quote> all()
 * @phpstan-method static list<Quote> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Quote> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Quote> findBy(array $attributes)
 * @phpstan-method static list<Quote> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Quote> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Quote> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Quote> sequence((iterable | callable) $sequence)
 * @extends PersistentObjectFactory<Quote>
 */
final class QuoteFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'company' => CompanyFactory::random(),
            'due' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'status' => self::faker()->randomElement(QuoteStatus::cases()),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => new Discount()
                ->setType(self::faker()->randomElement([Discount::TYPE_PERCENTAGE, Discount::TYPE_MONEY]))
                ->setValueMoney(self::faker()->randomNumber())
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    public static function class(): string
    {
        return Quote::class;
    }
}
