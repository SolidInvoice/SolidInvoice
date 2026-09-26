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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30100_8;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use function bin2hex;
use function random_bytes;

/**
 * `Version30100_8` adds a `NOT NULL DEFAULT` column, which backfills pre-existing rows as part
 * of the DDL itself. SQLite honours `ADD COLUMN ... DEFAULT` differently enough from the engines
 * users actually run that only a real engine proves the backfill — see the `schema` document on
 * SOL-82 §6. This test runs against the real connection the current test matrix provides (see
 * `.github/workflows/db-tests.yml`), in a throwaway database created for the duration of the test
 * so the migration's DDL — which commits immediately on MariaDB regardless of any surrounding
 * transaction — never touches the shared application test database.
 *
 * @see Version30100_8
 */
#[Group('functional')]
final class Version30100_8Test extends KernelTestCase
{
    private const string COLUMN = 'invoice_type_code';

    private ?Connection $connection = null;

    private ?Connection $adminConnection = null;

    private ?string $databaseName = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $appConnection = self::getContainer()->get('doctrine')->getConnection();
        $platform = $appConnection->getDatabasePlatform();

        if ($platform instanceof SQLitePlatform) {
            self::markTestSkipped(sprintf(
                '%s proves a NOT NULL DEFAULT backfill on the engines users actually run; SQLite cannot '
                . 'create the throwaway database this test needs (current platform: %s).',
                Version30100_8::class,
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

        $this->databaseName = 'solidinvoice_version30100_8_' . bin2hex(random_bytes(4));

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

        $this->buildPreMigrationSchema();
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
    public function testUpAddsTheColumnAndBackfillsExistingRows(): void
    {
        $invoiceId = new Ulid();
        $creditNoteId = new Ulid();

        $this->connection->insert('invoices', ['id' => $invoiceId], ['id' => UlidType::NAME]);
        $this->connection->insert('credit_notes', ['id' => $creditNoteId], ['id' => UlidType::NAME]);

        $this->applyUp();

        $schema = $this->connection->createSchemaManager()->introspectSchema();
        self::assertTrue($schema->getTable('invoices')->hasColumn(self::COLUMN));
        self::assertTrue($schema->getTable('credit_notes')->hasColumn(self::COLUMN));

        self::assertSame(
            '380',
            $this->connection->fetchOne('SELECT invoice_type_code FROM invoices WHERE id = ?', [$invoiceId], [UlidType::NAME]),
        );
        self::assertSame(
            '381',
            $this->connection->fetchOne('SELECT invoice_type_code FROM credit_notes WHERE id = ?', [$creditNoteId], [UlidType::NAME]),
        );
    }

    /**
     * @throws Exception
     */
    public function testDownDropsTheColumnFromBothTables(): void
    {
        $this->applyUp();
        $this->applyDown();

        $schema = $this->connection->createSchemaManager()->introspectSchema();
        self::assertFalse($schema->getTable('invoices')->hasColumn(self::COLUMN));
        self::assertFalse($schema->getTable('credit_notes')->hasColumn(self::COLUMN));
    }

    /**
     * The minimal pre-`_8` shape of the two tables this migration touches: just enough to insert
     * a row and prove the new column backfills it.
     *
     * @throws Exception
     */
    private function buildPreMigrationSchema(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $schema = new Schema();

        $invoices = $schema->createTable('invoices');
        $invoices->addColumn('id', UlidType::NAME);
        $invoices->setPrimaryKey(['id']);

        $creditNotes = $schema->createTable('credit_notes');
        $creditNotes->addColumn('id', UlidType::NAME);
        $creditNotes->setPrimaryKey(['id']);

        foreach ($schema->toSql($platform) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * @throws Exception
     */
    private function applyUp(): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $this->migration()->up($toSchema);
        $this->executeDiff($fromSchema, $toSchema);
    }

    /**
     * @throws Exception
     */
    private function applyDown(): void
    {
        $fromSchema = $this->connection->createSchemaManager()->introspectSchema();
        $toSchema = clone $fromSchema;

        $this->migration()->down($toSchema);
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

    private function migration(): Version30100_8
    {
        return new Version30100_8($this->connection, new NullLogger());
    }
}
