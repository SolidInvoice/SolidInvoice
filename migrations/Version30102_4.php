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

namespace DoctrineMigrations;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function bin2hex;
use function sprintf;
use function str_split;
use function vsprintf;

/**
 * Closes the remaining platform-independent drift between the migration chain and the
 * current ORM mapping: columns the chain never altered, index names the chain
 * hard-coded where the mapping expects Doctrine's generated name, and stale composite
 * indexes left over from when `company_id` was part of every index.
 *
 * Every change here is guarded on introspected state, so it is a clean no-op against a
 * fresh install (built straight from the ORM mapping by
 * {@see \SolidInvoice\InstallBundle\Installer\Database\Migration}) and only acts on an
 * upgraded install that still carries the old shape.
 *
 * `recurring_invoices.frequency`, `recurring_options` gaining a primary key on `id` and
 * its unique index are deliberately left untouched: they are irreversible and sit on top
 * of a pre-existing data-migration gap, tracked separately from this issue.
 */
final class Version30102_4 extends AbstractMigration
{
    /**
     * `(…, company_id)` variants of an index the mapping still declares on its own
     * (single-column, non-unique) terms. Safe to drop: the lookup each one backed is
     * still served by the surviving index plus the per-tenant `CompanyFilter` index.
     *
     * @var array<string, list<string>>
     */
    private const array STALE_COMPANY_COMPOSITE_INDEXES = [
        'contacts' => ['IDX_3340157319EB6921979B1AD6'],
        'addresses' => ['IDX_6FCA751619EB6921979B1AD6'],
        'invoice_lines' => [
            'IDX_72DBDC23B2A824D8979B1AD6',
            'IDX_72DBDC23416CCF0F979B1AD6',
            'IDX_72DBDC232989F1FD979B1AD6',
            // Not in SOL-375's catalogue: the same (recurringInvoice_id, company_id)
            // composite under the pre-rename column hash, confirmed redundant the same
            // way - a real upgrade history can carry this name instead of (or alongside)
            // IDX_72DBDC23416CCF0F979B1AD6 above, depending on when it last ran Version20305.
            'IDX_72DBDC23E31CCDF979B1AD6',
        ],
        'invoices' => ['IDX_6A2F2F95DB805178979B1AD6', 'IDX_6A2F2F9519EB6921979B1AD6'],
        'invoice_contact' => ['IDX_BEBBD0EB2989F1FD979B1AD6'],
        'recurring_invoices' => ['IDX_FE93E28419EB6921979B1AD6'],
        'payments' => [
            'IDX_65D29B3219883967979B1AD6',
            'IDX_65D29B322989F1FD979B1AD6',
            'IDX_65D29B32C7440455979B1AD6',
        ],
        'quotes' => ['IDX_A1B588C519EB6921979B1AD6'],
        'quote_contact' => ['IDX_A38D4EBCDB805178979B1AD6'],
        'quote_lines' => ['IDX_42FE01F7B2A824D8979B1AD6', 'IDX_42FE01F7DB805178979B1AD6'],
        'api_token_history' => ['IDX_61D8DC4441DEE7B9979B1AD6'],
        'users' => ['IDX_1483A5E976F5C865'],
    ];

    /**
     * Indexes Doctrine's generated name diverges from because an earlier migration
     * hard-coded a lowercase or differently-hashed name instead. Same columns, same
     * uniqueness, just the name the mapping expects instead of the one the chain gave it.
     *
     * @var array<string, array{0: string, 1: string, 2: list<string>, 3: bool}>
     */
    private const array RENAMED_INDEXES = [
        'companies' => ['uniq_companies_custom_domain', 'UNIQ_8244AA3A9304CA0F', ['custom_domain'], true],
        'mcp_refresh_token' => ['uniq_mcp_refresh_token_identifier', 'UNIQ_7C5FE04772E836A', ['identifier'], true],
        'mcp_oauth_auth_code' => ['uniq_mcp_oauth_auth_code_identifier', 'UNIQ_33244474772E836A', ['identifier'], true],
        'mcp_access_token' => ['uniq_mcp_access_token_jti', 'UNIQ_8611E117C53CF2EA', ['jti'], true],
        // PostgreSQL only: MySQL/MariaDB already generated this FK index under the name
        // the mapping expects. Guarded by hasIndex(), so the no-op on MySQL/MariaDB
        // falls out of the generic check rather than a platform branch here.
        'invoice_lines' => ['idx_72dbdc23e31ccdf', 'IDX_72DBDC23416CCF0F', ['recurringInvoice_id'], false],
    ];

    /** @var list<string> */
    private const array MCP_UPDATED_NOT_NULL_TABLES = [
        'mcp_consent_grant',
        'mcp_refresh_token',
        'mcp_oauth_auth_code',
        'mcp_oauth_client',
        'mcp_access_token',
    ];

    /** @var list<string> */
    private const array MCP_REVOKED_TABLES = ['mcp_refresh_token', 'mcp_oauth_auth_code', 'mcp_access_token'];

    /**
     * `invoices`/`quotes.uuid` used Symfony's `uuid` DBAL type, which packs the value as
     * `BINARY(16)` on MySQL/MariaDB (PostgreSQL already stores it as a native `uuid`,
     * whose implicit cast to `varchar` is lossless). {@see convertPackedUuidColumn()}.
     *
     * @var list<string>
     */
    private const array UUID_STRING_TABLES = ['invoices', 'quotes'];

    public function getDescription(): string
    {
        return 'Apply the remaining column, nullability, index and FK drift against the current ORM mapping';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function preUp(Schema $schema): void
    {
        // `due`/`paid_date`/`invoice_date` narrow from a timestamp to a bare DATE. That
        // only loses data if a row actually carries a non-midnight time component, which
        // the column was never supposed to hold in the first place - fail loudly instead
        // of silently truncating it.
        $this->assertDateOnly('invoices', 'due');
        $this->assertDateOnly('invoices', 'paid_date');
        $this->assertDateOnly('invoices', 'invoice_date');
        $this->assertDateOnly('quotes', 'due');

        // invoice_date is backfilled from `created` for every pre-existing row and set by
        // the entity constructor for every new one, so it is never null in practice - but
        // it was nullable at the DB level until now, and an ALTER that adds NOT NULL while
        // one still is fails loudly mid-batch on MySQL/MariaDB (no transaction to roll the
        // already-applied statements back). Check for it here instead, before anything runs.
        $this->assertNotNull('invoices', 'invoice_date');
    }

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->applyColumnChanges($schema);

        foreach (self::RENAMED_INDEXES as $tableName => [$oldName, $newName, $columns, $unique]) {
            $this->renameIndex($schema, $tableName, $oldName, $newName, $columns, $unique);
        }

        // client_credit's unique index covers the same columns as before, just in the
        // order the mapping's `#[ORM\UniqueConstraint(columns: ['company_id', 'client_id'])]`
        // declares them in, rather than the order an earlier migration created it in.
        $this->renameIndex(
            $schema,
            'client_credit',
            'UNIQ_4967254D19EB6921979B1AD6',
            'UNIQ_4967254D979B1AD619EB6921',
            ['company_id', 'client_id'],
            true,
        );

        foreach (self::STALE_COMPANY_COMPOSITE_INDEXES as $tableName => $indexNames) {
            $table = $schema->getTable($tableName);

            foreach ($indexNames as $indexName) {
                if ($table->hasIndex($indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        }

        $exportJobs = $schema->getTable('export_jobs');

        if ($exportJobs->hasForeignKey('FK_8E225B2E18C491A5')) {
            $exportJobs->removeForeignKey('FK_8E225B2E18C491A5');
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach (self::UUID_STRING_TABLES as $tableName) {
            $this->convertPackedUuidColumn($schema, $tableName);
        }
    }

    /**
     * @throws SchemaException
     */
    private function applyColumnChanges(Schema $schema): void
    {
        $invoiceLines = $schema->getTable('invoice_lines');
        $invoiceLines->getColumn('type')->setNotnull(true);

        if ($invoiceLines->hasColumn('recurringInvoice_id')) {
            // PostgreSQL only; already nullable on MySQL/MariaDB, so this is a no-op there.
            $invoiceLines->getColumn('recurringInvoice_id')->setNotnull(false);
        }

        $invoices = $schema->getTable('invoices');
        $this->narrowToString($invoices, 'uuid', 36);
        $this->narrowToDate($invoices, 'due');
        $this->narrowToDate($invoices, 'paid_date');
        $this->narrowToDate($invoices, 'invoice_date');
        $invoices->getColumn('invoice_date')->setNotnull(true);

        $quotes = $schema->getTable('quotes');
        $this->narrowToString($quotes, 'uuid', 36);
        $this->narrowToDate($quotes, 'due');

        $schema->getTable('users')->getColumn('trusted_version')->setNotnull(true);

        $schema->getTable('notification_user_setting')->getColumn('user_id')->setNotnull(false);
        $schema->getTable('notification_transport_setting')->getColumn('user_id')->setNotnull(false);

        $schema->getTable('recurring_options')->getColumn('endOccurrence')->setNotnull(false);

        foreach (self::MCP_UPDATED_NOT_NULL_TABLES as $tableName) {
            $schema->getTable($tableName)->getColumn('updated')->setNotnull(true);
        }

        foreach (self::MCP_REVOKED_TABLES as $tableName) {
            // The mapping declares no default for `revoked`; an earlier migration gave it
            // one at the DB level. Dropping it is enough on both platforms: PostgreSQL's
            // copy is already NOT NULL, MySQL/MariaDB's CHANGE statement re-states NOT
            // NULL as part of redefining the column without a DEFAULT clause.
            $schema->getTable($tableName)->getColumn('revoked')->setDefault(null);
        }
    }

    /**
     * @throws SchemaException
     */
    private function narrowToString(Table $table, string $columnName, int $length): void
    {
        $column = $table->getColumn($columnName);

        if (Type::lookupName($column->getType()) === Types::BINARY) {
            // Packed BINARY storage needs an explicit byte-to-text conversion
            // ({@see convertPackedUuidColumn()}), not a type relabel - leave it for
            // postUp() to handle once the rest of this migration's DDL has run.
            return;
        }

        $column->setType(Type::getType(Types::STRING));
        $column->setFixed(false);
        $column->setLength($length);
    }

    /**
     * @throws SchemaException
     */
    private function narrowToDate(Table $table, string $columnName): void
    {
        $table->getColumn($columnName)->setType(Type::getType(Types::DATE_IMMUTABLE));
    }

    /**
     * @param list<string> $columns
     *
     * @throws SchemaException
     */
    private function renameIndex(
        Schema $schema,
        string $tableName,
        string $oldName,
        string $newName,
        array $columns,
        bool $unique,
    ): void {
        $table = $schema->getTable($tableName);

        if (! $table->hasIndex($oldName) || $table->hasIndex($newName)) {
            return;
        }

        $table->dropIndex($oldName);

        if ($unique) {
            $table->addUniqueIndex($columns, $newName);
        } else {
            $table->addIndex($columns, $newName);
        }
    }

    /**
     * due/paid_date/invoice_date narrow from a timestamp to a bare DATE. A row with a
     * non-midnight time component would have it silently truncated by that cast, so fail
     * loudly instead - the column was never meant to carry one in the first place.
     *
     * Checked in PHP rather than SQL: there is no portable way to extract a time
     * component (SQLite has no EXTRACT()).
     *
     * @throws Exception
     */
    private function assertDateOnly(string $table, string $column): void
    {
        // ponytail: streams every non-null value through PHP; fine for a one-off upgrade check.
        $values = $this->connection->createQueryBuilder()
            ->select($column)
            ->from($table)
            ->where($this->connection->createExpressionBuilder()->isNotNull($column))
            ->executeQuery()
            ->iterateColumn();

        $hasTimeComponent = false;

        foreach ($values as $value) {
            if (new DateTimeImmutable((string) $value)->format('H:i:s.u') !== '00:00:00.000000') {
                $hasTimeComponent = true;
                break;
            }
        }

        $this->abortIf(
            $hasTimeComponent,
            sprintf(
                '%s.%s has a row with a non-midnight time component; narrowing it to DATE would silently '
                . 'truncate it. Resolve the offending rows before running this migration.',
                $table,
                $column,
            ),
        );
    }

    /**
     * @throws Exception
     */
    private function assertNotNull(string $table, string $column): void
    {
        $hasNull = $this->connection->createQueryBuilder()
            ->select('1')
            ->from($table)
            ->where($this->connection->createExpressionBuilder()->isNull($column))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        $this->abortIf(
            $hasNull !== false,
            sprintf(
                '%s.%s has a null row; the mapping now requires NOT NULL. Backfill it before running this '
                . 'migration.',
                $table,
                $column,
            ),
        );
    }

    /**
     * Converts a column packed by Symfony's `uuid` DBAL type - `BINARY(16)` on
     * MySQL/MariaDB - into the plain `VARCHAR(36)` the mapping now declares.
     *
     * A bare type change would reinterpret the 16 raw bytes as if they already were the
     * 36-character text form, corrupting every existing value. So the text form goes into
     * a temporary column first, formatted in PHP so no platform-specific SQL function
     * (`BIN_TO_UUID()`, which MariaDB has never implemented, or `HEX()`) is needed, and
     * then replaces the packed column.
     *
     * No-op on PostgreSQL (its native `uuid` already cast losslessly to `varchar` in
     * up()) and on a fresh install, where the column is `VARCHAR(36)` from the start.
     *
     * @throws Exception
     */
    private function convertPackedUuidColumn(Schema $schema, string $tableName): void
    {
        $column = $schema->getTable($tableName)->getColumn('uuid');

        if (Type::lookupName($column->getType()) !== Types::BINARY) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();

        $tempColumn = Column::editor()
            ->setUnquotedName('uuid_migrated')
            ->setTypeName(Types::STRING)
            ->setLength(36)
            ->setNotNull(false)
            ->create();

        $schemaManager->alterTable(new TableDiff(
            $schemaManager->introspectTableByUnquotedName($tableName),
            addedColumns: [$tempColumn],
        ));

        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'uuid')
            ->from($tableName)
            ->where($this->connection->createExpressionBuilder()->isNotNull('uuid'))
            ->executeQuery()
            ->fetchAllAssociative();

        // ponytail: one UPDATE per row, fine for a one-off upgrade; batch it if a tenant's upgrade is too slow.
        foreach ($rows as $row) {
            $this->connection->update(
                $tableName,
                ['uuid_migrated' => vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex((string) $row['uuid']), 4))],
                ['id' => $row['id']],
                ['id' => ParameterType::BINARY],
            );
        }

        // Two diffs: drop the packed column before the temporary one takes its name.
        $table = $schemaManager->introspectTableByUnquotedName($tableName);
        $schemaManager->alterTable(new TableDiff($table, droppedColumns: [$table->getColumn('uuid')]));

        $table = $schemaManager->introspectTableByUnquotedName($tableName);
        $migrated = $table->getColumn('uuid_migrated');
        $schemaManager->alterTable(new TableDiff($table, changedColumns: [
            'uuid_migrated' => new ColumnDiff($migrated, $migrated->edit()->setUnquotedName('uuid')->setNotNull(true)->create()),
        ]));
    }
}
