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

use DateTimeImmutable;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method InvoiceReminder|Proxy<InvoiceReminder> create((array | callable) $attributes = [])
 * @method static InvoiceReminder|Proxy<InvoiceReminder> createOne(array $attributes = [])
 * @method static InvoiceReminder|Proxy<InvoiceReminder> find((object | array | mixed) $criteria)
 * @method static InvoiceReminder|Proxy<InvoiceReminder> findOrCreate(array $attributes)
 * @method static InvoiceReminder|Proxy<InvoiceReminder> first(string $sortedField = 'id')
 * @method static InvoiceReminder|Proxy<InvoiceReminder> last(string $sortedField = 'id')
 * @method static InvoiceReminder|Proxy<InvoiceReminder> random(array $attributes = [])
 * @method static InvoiceReminder|Proxy<InvoiceReminder> randomOrCreate(array $attributes = [])
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] all()
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] createSequence((iterable | callable) $sequence)
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] findBy(array $attributes)
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static InvoiceReminder[]|Proxy<InvoiceReminder>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(InvoiceReminder | Proxy)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(InvoiceReminder | Proxy)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<InvoiceReminder, InvoiceReminderRepository> repository()
 *
 * @phpstan-method InvoiceReminder&Proxy<InvoiceReminder> create((array | callable) $attributes = [])
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> createOne(array $attributes = [])
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> find((object | array | mixed) $criteria)
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> findOrCreate(array $attributes)
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> first(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> last(string $sortedField = 'id')
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> random(array $attributes = [])
 * @phpstan-method static InvoiceReminder&Proxy<InvoiceReminder> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> all()
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> findBy(array $attributes)
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<InvoiceReminder&Proxy<InvoiceReminder>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<InvoiceReminder&Proxy<InvoiceReminder>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<InvoiceReminder&Proxy<InvoiceReminder>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<InvoiceReminder>
 */
final class InvoiceReminderFactory extends PersistentProxyObjectFactory
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
            'sentAt' => new DateTimeImmutable(),
        ];
    }

    public static function class(): string
    {
        return InvoiceReminder::class;
    }
}
