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
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method Invoice create((array | callable) $attributes = [])
 * @method static Invoice createOne(array $attributes = [])
 * @method static Invoice find((object | array | mixed) $criteria)
 * @method static Invoice findOrCreate(array $attributes)
 * @method static Invoice first(string $sortedField = 'id')
 * @method static Invoice last(string $sortedField = 'id')
 * @method static Invoice random(array $attributes = [])
 * @method static Invoice randomOrCreate(array $attributes = [])
 * @method static Invoice[] all()
 * @method static Invoice[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Invoice[] createSequence((iterable | callable) $sequence)
 * @method static Invoice[] findBy(array $attributes)
 * @method static Invoice[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Invoice[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<Invoice> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<Invoice> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<Invoice, InvoiceRepository> repository()
 *
 * @phpstan-method Invoice create((array | callable) $attributes = [])
 * @phpstan-method static Invoice createOne(array $attributes = [])
 * @phpstan-method static Invoice find((object | array | mixed) $criteria)
 * @phpstan-method static Invoice findOrCreate(array $attributes)
 * @phpstan-method static Invoice first(string $sortedField = 'id')
 * @phpstan-method static Invoice last(string $sortedField = 'id')
 * @phpstan-method static Invoice random(array $attributes = [])
 * @phpstan-method static Invoice randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Invoice> all()
 * @phpstan-method static list<Invoice> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Invoice> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Invoice> findBy(array $attributes)
 * @phpstan-method static list<Invoice> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Invoice> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Invoice> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Invoice> sequence((iterable | callable) $sequence)
 * @extends PersistentObjectFactory<Invoice>
 */
final class InvoiceFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            // 'uuid' => Uuid::v7(),
            'due' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'paidDate' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'status' => self::faker()->randomElement(InvoiceStatus::cases()),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'balance' => BigInteger::of(self::faker()->randomNumber()),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => new Discount()
                ->setType(self::faker()->randomElement([Discount::TYPE_PERCENTAGE, Discount::TYPE_MONEY]))
                ->setValueMoney(BigInteger::of(self::faker()->randomNumber()))
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    public static function class(): string
    {
        return Invoice::class;
    }
}
