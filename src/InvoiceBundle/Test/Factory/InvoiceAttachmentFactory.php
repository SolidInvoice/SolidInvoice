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

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use SolidInvoice\InvoiceBundle\Repository\InvoiceAttachmentRepository;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method InvoiceAttachment create((array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceAttachment createOne(array<string, mixed> $attributes = [])
 * @method static InvoiceAttachment find((object | array<string, mixed> | mixed) $criteria)
 * @method static InvoiceAttachment findOrCreate(array<string, mixed> $attributes)
 * @method static InvoiceAttachment first(string $sortedField = 'id')
 * @method static InvoiceAttachment last(string $sortedField = 'id')
 * @method static InvoiceAttachment random(array<string, mixed> $attributes = [])
 * @method static InvoiceAttachment randomOrCreate(array<string, mixed> $attributes = [])
 * @method static InvoiceAttachment[] all()
 * @method static InvoiceAttachment[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static InvoiceAttachment[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static InvoiceAttachment[] findBy(array<string, mixed> $attributes)
 * @method static InvoiceAttachment[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static InvoiceAttachment[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<InvoiceAttachment, InvoiceAttachmentFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<InvoiceAttachment, InvoiceAttachmentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<InvoiceAttachment, InvoiceAttachmentRepository> repository()
 *
 * @phpstan-method InvoiceAttachment create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static InvoiceAttachment createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceAttachment find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static InvoiceAttachment findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static InvoiceAttachment first(string $sortedField = 'id')
 * @phpstan-method static InvoiceAttachment last(string $sortedField = 'id')
 * @phpstan-method static InvoiceAttachment random(array<string, mixed> $attributes = [])
 * @phpstan-method static InvoiceAttachment randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceAttachment> all()
 * @phpstan-method static list<InvoiceAttachment> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<InvoiceAttachment> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<InvoiceAttachment> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<InvoiceAttachment> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<InvoiceAttachment> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<InvoiceAttachment, InvoiceAttachmentFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<InvoiceAttachment, InvoiceAttachmentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<InvoiceAttachment>
 */
final class InvoiceAttachmentFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'invoice' => InvoiceFactory::new(),
            'filename' => self::faker()->word() . '.pdf',
            'mimeType' => 'application/pdf',
            'fileSize' => self::faker()->numberBetween(1024, 1024 * 1024),
            'storageKey' => new Ulid()->toBase58(),
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return InvoiceAttachment::class;
    }
}
