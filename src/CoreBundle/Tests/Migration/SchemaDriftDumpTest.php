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

use PHPUnit\Framework\TestCase;

/**
 * {@see MigrationIsIdempotentAcrossLineagesTestCase} skips without a real database
 * (SOLIDINVOICE_DATABASE_URL), so it never runs in the `Unit` legs. Extending
 * {@see TestCase} directly instead keeps this helper's behaviour covered everywhere.
 */
final class SchemaDriftDumpTest extends TestCase
{
    public function testFragmentInsideCreateTableForAnAbsentTableIsDropped(): void
    {
        $dumpSql = <<<'SQL'
            ALTER TABLE invoices ADD payment_terms VARCHAR(25) DEFAULT 'custom' NOT NULL;
            CREATE TABLE credit_notes (id BINARY(16) NOT NULL, payment_terms VARCHAR(25) DEFAULT 'custom' NOT NULL, PRIMARY KEY(id))
            SQL;

        $scoped = SchemaDriftDump::scopedToExistingTables($dumpSql, ['invoices']);

        self::assertStringContainsString('invoices ADD payment_terms', $scoped);
        self::assertStringNotContainsString('CREATE TABLE credit_notes', $scoped);
    }

    public function testFragmentInsideAlterTableOnAPresentTableSurvives(): void
    {
        $dumpSql = "ALTER TABLE invoices ADD payment_terms VARCHAR(25) DEFAULT 'custom' NOT NULL";

        $scoped = SchemaDriftDump::scopedToExistingTables($dumpSql, ['invoices', 'credit_notes']);

        self::assertStringContainsString('invoices ADD payment_terms', $scoped);
    }

    public function testUnrecognisedStatementSurvives(): void
    {
        $dumpSql = 'SET FOREIGN_KEY_CHECKS = 0';

        $scoped = SchemaDriftDump::scopedToExistingTables($dumpSql, []);

        self::assertStringContainsString('SET FOREIGN_KEY_CHECKS = 0', $scoped);
    }

    public function testTableNameComparisonIgnoresCaseAndSchemaQualifier(): void
    {
        $dumpSql = 'CREATE TABLE credit_notes (id BINARY(16) NOT NULL, PRIMARY KEY(id))';

        $scoped = SchemaDriftDump::scopedToExistingTables($dumpSql, ['public.Credit_Notes']);

        self::assertStringContainsString('CREATE TABLE credit_notes', $scoped);
    }
}
