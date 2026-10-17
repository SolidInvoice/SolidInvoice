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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\DocumentCurrencyBackfill;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The installed schema comes from {@see DocumentCurrencyBackfill}'s ORM metadata; the migration
 * builds the same table by hand in {@see \DoctrineMigrations\Version30100_21::up()}. Without this
 * test the two drift into a permanent `doctrine:schema:validate` diff, and in this case the drift
 * would silently break `preDown()`. This is also the only test that proves the entity exists at
 * all, which the entity being mandatory (not decoration) depends on.
 */
#[Group('functional')]
final class DocumentCurrencyBackfillColumnsMatchMappingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testTheInstalledTableMatchesTheMigrationsDdl(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $connection);

        $table = $connection->createSchemaManager()->introspectTable(DocumentCurrencyBackfill::TABLE_NAME);

        $documentType = $table->getColumn('document_type');
        self::assertSame(20, $documentType->getLength());
        self::assertTrue($documentType->getNotnull());

        // Not asserted against UlidType by name: DBAL's SQLite introspection reports the
        // underlying column affinity, not the custom Doctrine type the mapping declared.
        self::assertTrue($table->getColumn('document_id')->getNotnull());
        self::assertTrue($table->getColumn('company_id')->getNotnull());

        $currencyCode = $table->getColumn('currency_code');
        self::assertSame(3, $currencyCode->getLength());
        self::assertFalse($currencyCode->getNotnull());

        $source = $table->getColumn('source');
        self::assertSame(20, $source->getLength());
        self::assertFalse($source->getNotnull());

        $outcome = $table->getColumn('outcome');
        self::assertSame(32, $outcome->getLength());
        self::assertTrue($outcome->getNotnull());

        self::assertTrue($table->getColumn('captured_at')->getNotnull());

        $primaryKey = $table->getPrimaryKey();
        self::assertNotNull($primaryKey);
        self::assertSame(['document_type', 'document_id'], $primaryKey->getColumns());

        self::assertTrue($this->hasNonPrimaryIndexOn($table->getIndexes(), 'company_id'));
    }

    /**
     * @param array<string, Index> $indexes
     */
    private function hasNonPrimaryIndexOn(array $indexes, string $column): bool
    {
        return array_any($indexes, fn ($index) => ! $index->isPrimary() && in_array($column, $index->getColumns(), true));
    }
}
