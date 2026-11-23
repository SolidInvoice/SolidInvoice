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

namespace SolidInvoice\EInvoiceBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Repository\EInvoiceDocumentRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method EInvoiceDocument create((array<string, mixed> | callable) $attributes = [])
 * @method static EInvoiceDocument createOne(array<string, mixed> $attributes = [])
 * @method static EInvoiceDocument find((object | array<string, mixed> | mixed) $criteria)
 * @method static EInvoiceDocument findOrCreate(array<string, mixed> $attributes)
 * @method static EInvoiceDocument first(string $sortedField = 'id')
 * @method static EInvoiceDocument last(string $sortedField = 'id')
 * @method static EInvoiceDocument random(array<string, mixed> $attributes = [])
 * @method static EInvoiceDocument randomOrCreate(array<string, mixed> $attributes = [])
 * @method static EInvoiceDocument[] all()
 * @method static EInvoiceDocument[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static EInvoiceDocument[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static EInvoiceDocument[] findBy(array<string, mixed> $attributes)
 * @method static EInvoiceDocument[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static EInvoiceDocument[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<EInvoiceDocument, EInvoiceDocumentFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<EInvoiceDocument, EInvoiceDocumentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<EInvoiceDocument, EInvoiceDocumentRepository> repository()
 *
 * @phpstan-method EInvoiceDocument create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static EInvoiceDocument createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static EInvoiceDocument find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static EInvoiceDocument findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static EInvoiceDocument first(string $sortedField = 'id')
 * @phpstan-method static EInvoiceDocument last(string $sortedField = 'id')
 * @phpstan-method static EInvoiceDocument random(array<string, mixed> $attributes = [])
 * @phpstan-method static EInvoiceDocument randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<EInvoiceDocument> all()
 * @phpstan-method static list<EInvoiceDocument> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<EInvoiceDocument> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<EInvoiceDocument> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<EInvoiceDocument> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<EInvoiceDocument> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<EInvoiceDocument, EInvoiceDocumentFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<EInvoiceDocument, EInvoiceDocumentFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<EInvoiceDocument>
 */
final class EInvoiceDocumentFactory extends PersistentObjectFactory
{
    /**
     * Produces a valid `pending` document with an invoice source. Terminal states are reached in
     * tests by applying transitions, never by a factory state that writes `status` directly — a
     * factory that bypasses the lifecycle would make the immutability tests pass for the wrong
     * reason.
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'company' => CompanyFactory::random(),
            'invoice' => InvoiceFactory::new(),
            'creditNote' => null,
            'sourceNumber' => self::faker()->bothify('INV-####'),
            'channel' => 'peppol',
            'profile' => 'peppol-bis-billing-3.0',
            'payload' => '<Invoice></Invoice>',
            'payloadFormat' => SyntaxFormat::Ubl,
            'payloadMediaType' => 'application/xml',
            'payloadFilename' => 'invoice-ubl.xml',
        ];
    }

    public static function class(): string
    {
        return EInvoiceDocument::class;
    }
}
