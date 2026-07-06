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
 * @method InvoiceReminder create((array | callable) $attributes = [])
 * @method static InvoiceReminder createOne(array $attributes = [])
 * @method static InvoiceReminder find((object | array | mixed) $criteria)
 * @method static InvoiceReminder findOrCreate(array $attributes)
 * @method static InvoiceReminder first(string $sortedField = 'id')
 * @method static InvoiceReminder last(string $sortedField = 'id')
 * @method static InvoiceReminder random(array $attributes = [])
 * @method static InvoiceReminder randomOrCreate(array $attributes = [])
 * @method static InvoiceReminder[] all()
 * @method static InvoiceReminder[] createMany(int $number, (array | callable) $attributes = [])
 * @method static InvoiceReminder[] createSequence((iterable | callable) $sequence)
 * @method static InvoiceReminder[] findBy(array $attributes)
 * @method static InvoiceReminder[] randomRange(int $min, int $max, array $attributes = [])
 * @method static InvoiceReminder[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<InvoiceReminder> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<InvoiceReminder> sequence((iterable|callable) $sequence)
 * @method static RepositoryDecorator<InvoiceReminder, InvoiceReminderRepository> repository()
 *
 * @phpstan-method InvoiceReminder create((array | callable) $attributes = [])
 * @phpstan-method static InvoiceReminder createOne(array $attributes = [])
 * @phpstan-method static InvoiceReminder find((object | array | mixed) $criteria)
 * @phpstan-method static InvoiceReminder findOrCreate(array $attributes)
 * @phpstan-method static InvoiceReminder first(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder last(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder random(array $attributes = [])
 * @phpstan-method static InvoiceReminder randomOrCreate(array $attributes = [])
 * @phpstan-method static list<InvoiceReminder> all()
 * @phpstan-method static list<InvoiceReminder> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<InvoiceReminder> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<InvoiceReminder> findBy(array $attributes)
 * @phpstan-method static list<InvoiceReminder> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<InvoiceReminder> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<InvoiceReminder> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<InvoiceReminder> sequence((iterable | callable) $sequence)
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
