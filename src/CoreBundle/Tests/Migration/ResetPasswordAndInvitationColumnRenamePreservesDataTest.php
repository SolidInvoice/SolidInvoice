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
use DoctrineMigrations\Version30100_23;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Proves the migration renames columns instead of dropping and recreating them: a row
 * written under the pre-rename column names is still there, unchanged, under the
 * post-rename names, and the same holds in reverse for down().
 *
 * This runs the migration the same way doctrine/migrations does: introspect the live
 * schema, let up()/down() mutate a clone of it, diff the two, and execute the resulting
 * SQL against the real connection (see Doctrine\Migrations\Provider\DBALSchemaDiffProvider).
 * SQLite is a stand-in for whichever real engine is configured; the cross-lineage harness
 * in {@see ResetPasswordAndInvitationColumnRenameMigrationTest} proves this against real
 * MariaDB/PostgreSQL schemas, so this test only has to prove the rename keeps the data.
 */
final class ResetPasswordAndInvitationColumnRenamePreservesDataTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $platform = $this->connection->getDatabasePlatform();

        $schema = new Schema();

        $users = $schema->createTable('users');
        $users->addColumn('id', UlidType::NAME);
        $users->setPrimaryKey(['id']);

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
    }

    public function testUpRenamesTheColumnsWithoutLosingTheRow(): void
    {
        $userId = (string) new Ulid();
        $this->connection->insert('users', ['id' => $userId]);
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

        $this->applyMigration(new Version30100_23($this->connection, new NullLogger()), up: true);

        // The PDO SQLite driver lower-cases result column names regardless of how the
        // query (or the underlying column) spells them, so the SELECT uses explicit
        // lower-case aliases: this asserts the renamed columns hold the row, not anything
        // about PDO's own case folding.
        $resetPasswordRequest = $this->connection->fetchAssociative(
            'SELECT selector, hashedToken AS hashed_token, requestedAt AS requested_at, expiresAt AS expires_at FROM reset_password_request'
        );
        self::assertSame([
            'selector' => 'a-selector',
            'hashed_token' => 'a-hashed-token',
            'requested_at' => '2026-01-01 10:00:00',
            'expires_at' => '2026-01-01 11:00:00',
        ], $resetPasswordRequest);

        $userInvitation = $this->connection->fetchAssociative(
            'SELECT expiresAt AS expires_at, reminderSentAt AS reminder_sent_at FROM user_invitations'
        );
        self::assertSame([
            'expires_at' => '2026-02-01 00:00:00',
            'reminder_sent_at' => '2026-01-25 00:00:00',
        ], $userInvitation);
    }

    public function testDownRenamesTheColumnsBackWithoutLosingTheRow(): void
    {
        $migration = new Version30100_23($this->connection, new NullLogger());

        $userId = (string) new Ulid();
        $this->connection->insert('users', ['id' => $userId]);
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

        $this->applyMigration($migration, up: true);
        $this->applyMigration($migration, up: false);

        $resetPasswordRequest = $this->connection->fetchAssociative(
            'SELECT selector, hashed_token, requested_at, expires_at FROM reset_password_request'
        );
        self::assertSame([
            'selector' => 'a-selector',
            'hashed_token' => 'a-hashed-token',
            'requested_at' => '2026-01-01 10:00:00',
            'expires_at' => '2026-01-01 11:00:00',
        ], $resetPasswordRequest);

        $userInvitation = $this->connection->fetchAssociative(
            'SELECT expires_at, reminder_sent_at FROM user_invitations'
        );
        self::assertSame([
            'expires_at' => '2026-02-01 00:00:00',
            'reminder_sent_at' => '2026-01-25 00:00:00',
        ], $userInvitation);
    }

    public function testUpIsANoOpOnceTheColumnsAreAlreadyRenamed(): void
    {
        $migration = new Version30100_23($this->connection, new NullLogger());

        $this->applyMigration($migration, up: true);

        self::assertSame([], $this->diffSql($migration, up: true));
    }

    /**
     * Runs up() or down() the way doctrine/migrations does: introspect the live schema,
     * mutate a clone through the migration, diff the two, and execute whatever SQL that
     * produces against the real connection.
     */
    private function applyMigration(Version30100_23 $migration, bool $up): void
    {
        foreach ($this->diffSql($migration, $up) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * @return list<string>
     */
    private function diffSql(Version30100_23 $migration, bool $up): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = clone $fromSchema;

        if ($up) {
            $migration->up($toSchema);
        } else {
            $migration->down($toSchema);
        }

        $comparator = $schemaManager->createComparator(new ComparatorConfig(reportModifiedIndexes: false));

        return $this->connection->getDatabasePlatform()->getAlterSchemaSQL(
            $comparator->compareSchemas($fromSchema, $toSchema)
        );
    }
}
