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

namespace SolidInvoice\CoreBundle\Tests\Functional\Migration;

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use DoctrineMigrations\Version30100_7;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * `Version30100_7` widens the `invoice_tax_exactly_one_document` CHECK constraint that
 * {@see \DoctrineMigrations\Version30000_9} added, and its `down()` drops FKs that reach back
 * into a table it also drops. Neither behaviour is observable on SQLite: SQLite neither
 * enforces `ALTER TABLE ADD CONSTRAINT CHECK` nor rejects a same-transaction FK ordering
 * problem the way MariaDB and PostgreSQL do. This is why {@see \SolidInvoice\CoreBundle\Tests\Migration\CreditNoteMigrationTest}
 * (SQLite) passed while the migration silently no-op'd the CHECK widening on MariaDB and
 * failed `down()` on both platforms — see SOL-160.
 *
 * This test runs against the real connection the current test matrix provides (see
 * `.github/workflows/db-tests.yml`), in a throwaway database created for the duration of the
 * test so the migration's DDL — which commits immediately on MariaDB regardless of any
 * surrounding transaction — never touches the shared application test database.
 *
 * @see Version30100_7
 */
#[Group('functional')]
final class Version30100_7Test extends KernelTestCase
{
    private const string CHECK_CONSTRAINT = 'invoice_tax_exactly_one_document';

    private ?Connection $connection = null;

    private ?Connection $adminConnection = null;

    private ?string $databaseName = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $appConnection = self::getContainer()->get('doctrine')->getConnection();
        $platform = $appConnection->getDatabasePlatform();

        if (! $platform instanceof MariaDBPlatform && ! $platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped(sprintf(
                '%s enforces a CHECK constraint at the database level; only MariaDB and PostgreSQL '
                . 'enforce it in this test matrix (current platform: %s).',
                Version30100_7::class,
                $platform::class,
            ));
        }

        $params = $appConnection->getParams();
        if (isset($params['primary'])) {
            $params = $params['primary'];
        }

        // dama/doctrine-test-bundle's StaticDriver keys its cached connection on
        // dama.connection_key alone and ignores dbname, so copying it here would hand back the
        // shared, already-in-transaction application test connection instead of a genuinely
        // separate one to the throwaway database. dbname_suffix is DAMA's own param and has no
        // meaning on a connection it does not manage.
        unset($params['dama.connection_key'], $params['dbname_suffix']);

        $this->databaseName = 'solidinvoice_version30100_7_' . bin2hex(random_bytes(4));

        $adminParams = $params;
        unset($adminParams['dbname']);
        $adminParams['dbname'] = $platform instanceof PostgreSQLPlatform
            ? ($params['default_dbname'] ?? 'postgres')
            : 'mysql';

        $this->adminConnection = DriverManager::getConnection($adminParams, $appConnection->getConfiguration());
        $this->adminConnection->createSchemaManager()->createDatabase(
            $this->adminConnection->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName),
        );

        $connectionParams = $params;
        $connectionParams['dbname'] = $this->databaseName;
        $this->connection = DriverManager::getConnection($connectionParams, $appConnection->getConfiguration());

        $this->buildPreCreditNoteSchema();
    }

    protected function tearDown(): void
    {
        $this->connection?->close();

        if ($this->adminConnection instanceof Connection && $this->databaseName !== null) {
            $this->adminConnection->createSchemaManager()->dropDatabase(
                $this->adminConnection->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName),
            );
            $this->adminConnection->close();
        }

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testUpWidensTheCheckConstraintToIncludeCreditNoteId(): void
    {
        $this->applyUp();

        $schema = $this->connection->createSchemaManager()->introspectSchema();

        self::assertTrue($schema->hasTable('credit_notes'));
        self::assertTrue($schema->getTable('invoice_lines')->hasColumn('credit_note_id'));
        self::assertTrue($schema->getTable('invoice_tax')->hasColumn('credit_note_id'));

        $checkClause = $this->fetchCheckClause();
        self::assertNotNull($checkClause, 'invoice_tax_exactly_one_document is missing after up()/postUp() — Defect 1 (MariaDB DROP CHECK) regressed.');
        self::assertStringContainsString('credit_note_id', $checkClause);
    }

    /**
     * @throws Exception
     */
    public function testCreditNoteOwnedInvoiceTaxRowInsertsSuccessfully(): void
    {
        $this->applyUp();

        $creditNoteId = $this->insertCreditNote();

        $this->connection->insert('invoice_tax', [
            'id' => new Ulid(),
            'amount' => BigInteger::of(0),
            'credit_note_id' => $creditNoteId,
        ], [
            'id' => UlidType::NAME,
            'amount' => BigIntegerType::NAME,
            'credit_note_id' => UlidType::NAME,
        ]);

        self::assertSame(
            1,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM invoice_tax WHERE credit_note_id IS NOT NULL'),
        );
    }

    /**
     * @throws Exception
     */
    public function testInvoiceTaxRowWithTwoOwnersIsRejected(): void
    {
        $this->applyUp();

        $creditNoteId = $this->insertCreditNote();
        $invoiceId = new Ulid();
        $this->connection->insert('invoices', ['id' => $invoiceId], ['id' => UlidType::NAME]);

        $this->expectException(Exception::class);

        $this->connection->insert('invoice_tax', [
            'id' => new Ulid(),
            'amount' => BigInteger::of(0),
            'invoice_id' => $invoiceId,
            'credit_note_id' => $creditNoteId,
        ], [
            'id' => UlidType::NAME,
            'amount' => BigIntegerType::NAME,
            'invoice_id' => UlidType::NAME,
            'credit_note_id' => UlidType::NAME,
        ]);
    }

    /**
     * @throws Exception
     */
    public function testDownRevertsColumnsTableAndCheckConstraintOnBothPlatforms(): void
    {
        $this->applyUp();
        $this->applyDown();

        $schema = $this->connection->createSchemaManager()->introspectSchema();

        self::assertFalse($schema->hasTable('credit_notes'));
        self::assertFalse($schema->getTable('invoice_lines')->hasColumn('credit_note_id'));
        self::assertFalse($schema->getTable('invoice_tax')->hasColumn('credit_note_id'));

        $checkClause = $this->fetchCheckClause();
        self::assertNotNull($checkClause, 'invoice_tax_exactly_one_document was not restored by down() — Defect 2 (down() failing before reaching preDown()' . "'" . ' revert) regressed.');
        self::assertStringNotContainsString('credit_note_id', $checkClause);
    }

    /**
     * Builds the schema as it exists immediately after {@see \DoctrineMigrations\Version30000_9}:
     * `invoice_lines`/`invoice_tax` without a `credit_note_id` column, and the three-column
     * `invoice_tax_exactly_one_document` CHECK already in place. `Version30100_7::postUp()` has
     * to replace this exact constraint — this is the state that reproduces Defect 1, since
     * without a pre-existing CHECK to drop, `postUp()` would only ever exercise the `ADD` path.
     *
     * @throws Exception
     */
    private function buildPreCreditNoteSchema(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $schema = new Schema();

        $companies = $schema->createTable('companies');
        $companies->addColumn('id', UlidType::NAME);
        $companies->setPrimaryKey(['id']);

        $clients = $schema->createTable('clients');
        $clients->addColumn('id', UlidType::NAME);
        $clients->setPrimaryKey(['id']);

        $invoices = $schema->createTable('invoices');
        $invoices->addColumn('id', UlidType::NAME);
        $invoices->setPrimaryKey(['id']);

        $invoiceLines = $schema->createTable('invoice_lines');
        $invoiceLines->addColumn('id', UlidType::NAME);
        $invoiceLines->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
        $invoiceLines->setPrimaryKey(['id']);
        $invoiceLines->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        $invoiceTax = $schema->createTable('invoice_tax');
        $invoiceTax->addColumn('id', UlidType::NAME);
        $invoiceTax->addColumn('invoice_id', UlidType::NAME, ['notnull' => false]);
        $invoiceTax->addColumn('quote_id', UlidType::NAME, ['notnull' => false]);
        $invoiceTax->addColumn('recurring_invoice_id', UlidType::NAME, ['notnull' => false]);
        $invoiceTax->addColumn('amount', BigIntegerType::NAME, ['notnull' => true]);
        $invoiceTax->setPrimaryKey(['id']);
        $invoiceTax->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);

        foreach ($schema->toSql($platform) as $sql) {
            $this->connection->executeStatement($sql);
        }

        // The three-column form Version30000_9::addInvoiceTaxCheckConstraint() builds. Inlined
        // rather than reused: that method is private, and frozen by design — each migration's
        // check-building SQL stays independent of the others (see Version30100_7's own copy).
        $this->connection->executeStatement(sprintf(
            'ALTER TABLE invoice_tax ADD CONSTRAINT %s CHECK ('
            . '(invoice_id IS NOT NULL AND quote_id IS NULL AND recurring_invoice_id IS NULL) OR '
            . '(invoice_id IS NULL AND quote_id IS NOT NULL AND recurring_invoice_id IS NULL) OR '
            . '(invoice_id IS NULL AND quote_id IS NULL AND recurring_invoice_id IS NOT NULL)'
            . ')',
            self::CHECK_CONSTRAINT,
        ));
    }

    /**
     * @throws Exception
     */
    private function insertCreditNote(): Ulid
    {
        $companyId = new Ulid();
        $clientId = new Ulid();
        $creditNoteId = new Ulid();

        $this->connection->insert('companies', ['id' => $companyId], ['id' => UlidType::NAME]);
        $this->connection->insert('clients', ['id' => $clientId], ['id' => UlidType::NAME]);

        $this->connection->insert('credit_notes', [
            'id' => $creditNoteId,
            'company_id' => $companyId,
            'client_id' => $clientId,
            'credit_note_id' => 'CN-TEST-0001',
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'status' => 'draft',
            'credit_note_date' => CarbonImmutable::parse('2026-01-01'),
            'total_amount' => BigInteger::of(0),
            'baseTotal_amount' => BigInteger::of(0),
            'tax_amount' => BigInteger::of(0),
            'discount_valueMoney_amount' => BigInteger::of(0),
            'created' => CarbonImmutable::now(),
            'updated' => CarbonImmutable::now(),
        ], [
            'id' => UlidType::NAME,
            'company_id' => UlidType::NAME,
            'client_id' => UlidType::NAME,
            'credit_note_id' => Types::STRING,
            'uuid' => Types::STRING,
            'status' => Types::STRING,
            'credit_note_date' => Types::DATE_IMMUTABLE,
            'total_amount' => BigIntegerType::NAME,
            'baseTotal_amount' => BigIntegerType::NAME,
            'tax_amount' => BigIntegerType::NAME,
            'discount_valueMoney_amount' => BigIntegerType::NAME,
            'created' => Types::DATETIME_IMMUTABLE,
            'updated' => Types::DATETIME_IMMUTABLE,
        ]);

        return $creditNoteId;
    }

    /**
     * @throws Exception
     */
    private function applyUp(): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $migration = $this->migration();
        $migration->up($toSchema);
        $this->executeDiff($fromSchema, $toSchema);
        $migration->postUp($toSchema);
    }

    /**
     * `DbalExecutor` hands `preDown()` a lazy `Schema` proxy ({@see \Doctrine\Migrations\Provider\LazySchemaDiffProvider})
     * that is not actually introspected from the database until something reads it — which
     * `Version30100_7::preDown()` never does, since it drops the inbound foreign keys straight
     * through the connection instead of mutating the `Schema` it is given. So by the time
     * `fromSchema` is finally introspected for the `down()` diff, `preDown()`'s DDL has already
     * committed and the dropped foreign keys are already gone from it. Introspecting only after
     * `preDown()` has run reproduces that ordering; introspecting before it — the previous
     * version of this method — hands the diff a `fromSchema` that still has the foreign keys
     * `preDown()` already dropped for real, so the diff emits a second, now-invalid `DROP
     * CONSTRAINT`/`DROP FOREIGN KEY` for each of them.
     *
     * @throws Exception
     */
    private function applyDown(): void
    {
        $migration = $this->migration();
        $migration->preDown($this->connection->createSchemaManager()->introspectSchema());

        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $migration->down($toSchema);
        $this->executeDiff($fromSchema, $toSchema);
    }

    /**
     * Mirrors what the Doctrine Migrations runner does with the `Schema` a migration mutates:
     * diff it against the schema as introspected before the migration ran, then execute the
     * platform-specific SQL the diff produces.
     *
     * @throws Exception
     */
    private function executeDiff(Schema $fromSchema, Schema $toSchema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $comparator = $this->connection->createSchemaManager()->createComparator();
        $diff = $comparator->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($diff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * Scoped to the current database/schema for the same reason {@see Version30100_7::constraintExists()}
     * is: `information_schema.check_constraints` spans every schema the connection user can see
     * on MariaDB, and every schema in the current database on PostgreSQL, so an unscoped lookup
     * can return another database's same-named constraint instead of this test's own throwaway
     * one — for example another `Version30100_7Test` throwaway database left over on a shared
     * MariaDB instance from an interrupted run.
     *
     * @throws Exception
     */
    private function fetchCheckClause(): ?string
    {
        $sql = 'SELECT check_clause FROM information_schema.check_constraints WHERE constraint_name = ?';
        $params = [self::CHECK_CONSTRAINT];

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $sql .= ' AND constraint_schema = current_schema()';
        } else {
            $sql .= ' AND constraint_schema = DATABASE()';
        }

        $clause = $this->connection->fetchOne($sql, $params);

        return $clause === false ? null : (string) $clause;
    }

    private function migration(): Version30100_7
    {
        return new Version30100_7($this->connection, new NullLogger());
    }
}
