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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\ComparatorConfig;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use DoctrineMigrations\Version30102_3;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function array_map;

/**
 * Proves the migration renames tables and columns instead of dropping and recreating them: a row
 * written under the pre-rename names is still there, unchanged, under the post-rename names, and
 * the same holds in reverse for down().
 *
 * This runs the migration the same way doctrine/migrations does: introspect the live schema, let
 * up()/down() mutate a clone of it, then execute the SQL the migration added followed by the diff
 * of the two schemas against the real connection (see Doctrine\Migrations\Version\DbalExecutor).
 * SQLite is a stand-in for whichever real engine is configured; the cross-lineage harness in
 * {@see Version30102_3CrossLineageMigrationTest} proves this against real MariaDB/PostgreSQL
 * schemas, so this test only has to prove the renames keep the data.
 */
final class Version30102_3PreservesDataTest extends TestCase
{
    private Connection $connection;

    private string $recurringInvoiceId;

    private string $contactId;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $platform = $this->connection->getDatabasePlatform();

        $schema = new Schema();

        foreach (['users', 'recurring_invoices', 'contacts'] as $name) {
            $table = $schema->createTable($name);
            $table->addColumn('id', UlidType::NAME);
            $table->setPrimaryKey(['id']);
        }

        $recurringInvoiceContact = $schema->createTable('recurringinvoice_contact');
        $recurringInvoiceContact->addColumn('recurringinvoice_id', UlidType::NAME);
        $recurringInvoiceContact->addColumn('contact_id', UlidType::NAME);
        $recurringInvoiceContact->setPrimaryKey(['recurringinvoice_id', 'contact_id']);
        $recurringInvoiceContact->addIndex(['recurringinvoice_id'], 'IDX_1673913EE31CCDF');
        $recurringInvoiceContact->addIndex(['contact_id'], 'IDX_1673913EE7A1254A');
        $recurringInvoiceContact->addForeignKeyConstraint('recurring_invoices', ['recurringinvoice_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_1673913EE31CCDF');
        $recurringInvoiceContact->addForeignKeyConstraint('contacts', ['contact_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_1673913EE7A1254A');

        $resetPasswordRequest = $schema->createTable('reset_password_request');
        $resetPasswordRequest->addColumn('id', UlidType::NAME);
        $resetPasswordRequest->addColumn('user_id', UlidType::NAME, ['notnull' => true]);
        $resetPasswordRequest->addColumn('selector', Types::STRING, ['length' => 20, 'notnull' => true]);
        $resetPasswordRequest->addColumn('hashed_token', Types::STRING, ['length' => 100, 'notnull' => true]);
        $resetPasswordRequest->addColumn('requested_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $resetPasswordRequest->addColumn('expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $resetPasswordRequest->setPrimaryKey(['id']);
        $resetPasswordRequest->addForeignKeyConstraint('users', ['user_id'], ['id']);

        $userInvitations = $schema->createTable('user_invitations');
        $userInvitations->addColumn('id', UlidType::NAME);
        $userInvitations->addColumn('expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $userInvitations->addColumn('reminder_sent_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $userInvitations->setPrimaryKey(['id']);

        foreach ($schema->toSql($platform) as $sql) {
            $this->connection->executeStatement($sql);
        }

        $userId = (string) new Ulid();
        $this->recurringInvoiceId = (string) new Ulid();
        $this->contactId = (string) new Ulid();

        $this->connection->insert('users', ['id' => $userId]);
        $this->connection->insert('recurring_invoices', ['id' => $this->recurringInvoiceId]);
        $this->connection->insert('contacts', ['id' => $this->contactId]);
        $this->connection->insert('recurringinvoice_contact', [
            'recurringinvoice_id' => $this->recurringInvoiceId,
            'contact_id' => $this->contactId,
        ]);
        $this->connection->insert('reset_password_request', [
            'id' => (string) new Ulid(),
            'user_id' => $userId,
            'selector' => 'a-selector',
            'hashed_token' => 'a-hashed-token',
            'requested_at' => '2026-01-01 10:00:00',
            'expires_at' => '2026-01-01 11:00:00',
        ]);
        $this->connection->insert('user_invitations', [
            'id' => (string) new Ulid(),
            'expires_at' => '2026-02-01 00:00:00',
            'reminder_sent_at' => '2026-01-25 00:00:00',
        ]);
    }

    public function testUpRenamesTheTablesAndColumnsWithoutLosingTheRows(): void
    {
        $this->applyMigration(up: true);

        self::assertSame(
            [['recurringinvoice_id' => $this->recurringInvoiceId, 'contact_id' => $this->contactId]],
            $this->connection->fetchAllAssociative('SELECT recurringinvoice_id, contact_id FROM recurringinvoice_contacts'),
        );

        // The PDO SQLite driver lower-cases result column names regardless of how the
        // query (or the underlying column) spells them, so the SELECT uses explicit
        // lower-case aliases: this asserts the renamed columns hold the row, not anything
        // about PDO's own case folding.
        self::assertSame([
            'selector' => 'a-selector',
            'hashed_token' => 'a-hashed-token',
            'requested_at' => '2026-01-01 10:00:00',
            'expires_at' => '2026-01-01 11:00:00',
        ], $this->connection->fetchAssociative(
            'SELECT selector, hashedToken AS hashed_token, requestedAt AS requested_at, expiresAt AS expires_at FROM reset_password_request'
        ));

        self::assertSame([
            'expires_at' => '2026-02-01 00:00:00',
            'reminder_sent_at' => '2026-01-25 00:00:00',
        ], $this->connection->fetchAssociative(
            'SELECT expiresAt AS expires_at, reminderSentAt AS reminder_sent_at FROM user_invitations'
        ));
    }

    public function testUpRenamesTheJoinTableIndexesAndForeignKeys(): void
    {
        $this->applyMigration(up: true);

        $table = $this->connection->createSchemaManager()->introspectTableByUnquotedName('recurringinvoice_contacts');

        self::assertTrue($table->hasIndex('IDX_DA79A131E31CCDF'));
        self::assertTrue($table->hasIndex('IDX_DA79A131E7A1254A'));
        self::assertTrue($table->hasForeignKey('FK_DA79A131E31CCDF'));
        self::assertTrue($table->hasForeignKey('FK_DA79A131E7A1254A'));
    }

    public function testDownRenamesTheTablesAndColumnsBackWithoutLosingTheRows(): void
    {
        $this->applyMigration(up: true);
        $this->applyMigration(up: false);

        self::assertSame(
            [['recurringinvoice_id' => $this->recurringInvoiceId, 'contact_id' => $this->contactId]],
            $this->connection->fetchAllAssociative('SELECT recurringinvoice_id, contact_id FROM recurringinvoice_contact'),
        );

        self::assertSame([
            'selector' => 'a-selector',
            'hashed_token' => 'a-hashed-token',
            'requested_at' => '2026-01-01 10:00:00',
            'expires_at' => '2026-01-01 11:00:00',
        ], $this->connection->fetchAssociative(
            'SELECT selector, hashed_token, requested_at, expires_at FROM reset_password_request'
        ));

        self::assertSame([
            'expires_at' => '2026-02-01 00:00:00',
            'reminder_sent_at' => '2026-01-25 00:00:00',
        ], $this->connection->fetchAssociative(
            'SELECT expires_at, reminder_sent_at FROM user_invitations'
        ));
    }

    public function testUpIsANoOpOnceEverythingIsRenamed(): void
    {
        $this->applyMigration(up: true);

        self::assertSame([], $this->migrationSql(up: true));
    }

    private function applyMigration(bool $up): void
    {
        foreach ($this->migrationSql($up) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * @return list<string>
     */
    private function migrationSql(bool $up): array
    {
        $migration = new Version30102_3($this->connection, new NullLogger());
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = clone $fromSchema;

        if ($up) {
            $migration->up($toSchema);
        } else {
            $migration->down($toSchema);
        }

        $comparator = $schemaManager->createComparator(new ComparatorConfig(reportModifiedIndexes: false));

        return [
            ...array_map(static fn ($query): string => (string) $query, $migration->getSql()),
            ...$this->connection->getDatabasePlatform()->getAlterSchemaSQL($comparator->compareSchemas($fromSchema, $toSchema)),
        ];
    }
}
