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

namespace SolidInvoice\InvoiceBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method RecurringInvoice create(array<string, mixed>|callable $attributes = [])
 * @method static RecurringInvoice createOne(array<string, mixed> $attributes = [])
 * @method static RecurringInvoice find(object|array<string, mixed>|mixed $criteria)
 * @method static RecurringInvoice findOrCreate(array<string, mixed> $attributes)
 * @method static RecurringInvoice first(string $sortedField = 'id')
 * @method static RecurringInvoice last(string $sortedField = 'id')
 * @method static RecurringInvoice random(array<string, mixed> $attributes = [])
 * @method static RecurringInvoice randomOrCreate(array<string, mixed> $attributes = [])
 * @method static RecurringInvoice[] all()
 * @method static RecurringInvoice[] createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @method static RecurringInvoice[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RecurringInvoice[] findBy(array<string, mixed> $attributes)
 * @method static RecurringInvoice[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static RecurringInvoice[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<RecurringInvoice, RecurringInvoiceFactory> many(int $min, int|null $max = null)
 * @method FactoryCollection<RecurringInvoice, RecurringInvoiceFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<RecurringInvoice, RecurringInvoiceRepository> repository()
 *
 * @phpstan-method RecurringInvoice create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static RecurringInvoice createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static RecurringInvoice find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static RecurringInvoice findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static RecurringInvoice first(string $sortedField = 'id')
 * @phpstan-method static RecurringInvoice last(string $sortedField = 'id')
 * @phpstan-method static RecurringInvoice random(array<string, mixed> $attributes = [])
 * @phpstan-method static RecurringInvoice randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<RecurringInvoice> all()
 * @phpstan-method static list<RecurringInvoice> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<RecurringInvoice> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<RecurringInvoice> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<RecurringInvoice> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<RecurringInvoice> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<RecurringInvoice, RecurringInvoiceFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<RecurringInvoice, RecurringInvoiceFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<RecurringInvoice>
 */
final class RecurringInvoiceFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'status' => self::faker()->randomElement(RecurringInvoiceStatus::cases()),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'dateStart' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => new Discount()
                ->setType(self::faker()->randomElement([Discount::TYPE_PERCENTAGE, Discount::TYPE_MONEY]))
                ->setValueMoney(BigInteger::of(self::faker()->randomNumber()))
                ->setValuePercentage(self::faker()->randomFloat()),
            'recurringOptions' => new RecurringOptions()
                ->setType(ScheduleRecurringType::WEEKLY)
                ->setEndType(ScheduleEndType::AFTER)
                ->setDays([1])
                ->setEndOccurrence(1),
        ];
    }

    public static function class(): string
    {
        return RecurringInvoice::class;
    }
}
