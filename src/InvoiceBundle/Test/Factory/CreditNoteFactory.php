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
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method CreditNote create((array<string, mixed> | callable) $attributes = [])
 * @method static CreditNote createOne(array<string, mixed> $attributes = [])
 * @method static CreditNote find((object | array<string, mixed> | mixed) $criteria)
 * @method static CreditNote findOrCreate(array<string, mixed> $attributes)
 * @method static CreditNote first(string $sortedField = 'id')
 * @method static CreditNote last(string $sortedField = 'id')
 * @method static CreditNote random(array<string, mixed> $attributes = [])
 * @method static CreditNote randomOrCreate(array<string, mixed> $attributes = [])
 * @method static CreditNote[] all()
 * @method static CreditNote[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static CreditNote[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static CreditNote[] findBy(array<string, mixed> $attributes)
 * @method static CreditNote[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static CreditNote[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<CreditNote, CreditNoteFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<CreditNote, CreditNoteFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<CreditNote, CreditNoteRepository> repository()
 *
 * @phpstan-method CreditNote create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static CreditNote createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static CreditNote find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static CreditNote findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static CreditNote first(string $sortedField = 'id')
 * @phpstan-method static CreditNote last(string $sortedField = 'id')
 * @phpstan-method static CreditNote random(array<string, mixed> $attributes = [])
 * @phpstan-method static CreditNote randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<CreditNote> all()
 * @phpstan-method static list<CreditNote> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<CreditNote> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<CreditNote> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<CreditNote> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<CreditNote> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<CreditNote, CreditNoteFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<CreditNote, CreditNoteFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<CreditNote>
 */
final class CreditNoteFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function defaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'status' => self::faker()->randomElement(CreditNoteStatus::cases()),
            'creditNoteDate' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'reason' => self::faker()->text(),
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
                ->setValueMoney(BigInteger::of(self::faker()->randomNumber()))
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    public static function class(): string
    {
        return CreditNote::class;
    }
}
