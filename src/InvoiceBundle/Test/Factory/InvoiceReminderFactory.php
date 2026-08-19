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

use Carbon\CarbonImmutable;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method InvoiceReminder create((array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceReminder createOne(array<string, mixed> $attributes = [])
 * @method static InvoiceReminder find((object | array<string, mixed> | mixed) $criteria)
 * @method static InvoiceReminder findOrCreate(array<string, mixed> $attributes)
 * @method static InvoiceReminder first(string $sortedField = 'id')
 * @method static InvoiceReminder last(string $sortedField = 'id')
 * @method static InvoiceReminder random(array<string, mixed> $attributes = [])
 * @method static InvoiceReminder randomOrCreate(array<string, mixed> $attributes = [])
 * @method static InvoiceReminder[] all()
 * @method static InvoiceReminder[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceReminder[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static InvoiceReminder[] findBy(array<string, mixed> $attributes)
 * @method static InvoiceReminder[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static InvoiceReminder[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<InvoiceReminder, InvoiceReminderFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<InvoiceReminder, InvoiceReminderFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<InvoiceReminder, InvoiceReminderRepository> repository()
 *
 * @phpstan-method InvoiceReminder create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static InvoiceReminder createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceReminder find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static InvoiceReminder findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static InvoiceReminder first(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder last(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder random(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceReminder randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceReminder> all()
 * @phpstan-method static list<InvoiceReminder> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<InvoiceReminder> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<InvoiceReminder> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<InvoiceReminder> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceReminder> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<InvoiceReminder, InvoiceReminderFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<InvoiceReminder, InvoiceReminderFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<InvoiceReminder>
 */
final class InvoiceReminderFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'invoice' => InvoiceFactory::new(),
            'company' => CompanyFactory::new(),
            'reminderType' => ReminderType::PreDue,
            'sentAt' => CarbonImmutable::now(),
        ];
    }

    public static function class(): string
    {
        return InvoiceReminder::class;
    }
}
