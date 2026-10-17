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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\Version30100_21;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function array_map;

/**
 * doctrine:schema:update's diff cannot express a rename: comparing `recurringinvoice_contact`
 * against the mapping's `recurringinvoice_contacts` on their own looks like one table dropped
 * and a different one created, which would delete every recurring-invoice/contact association.
 * This pins {@see Version30100_21} to a pure rename on both supported platforms: the SQL it
 * plans never drops or creates the table itself, so the rows in it are never touched.
 */
final class Version30100_21MigrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUpRenamesTheTableOnPostgreSQLWithoutDroppingOrCreatingIt(): void
    {
        $sql = $this->upStatements(new PostgreSQLPlatform(), $this->schemaWithOldTable());

        self::assertContains('ALTER TABLE recurringinvoice_contact RENAME TO recurringinvoice_contacts', $sql);
        $this->assertNoDropOrCreateTable($sql);
    }

    public function testUpRenamesTheTableOnMariaDbWithoutDroppingOrCreatingIt(): void
    {
        $sql = $this->upStatements(new MariaDBPlatform(), $this->schemaWithOldTable());

        self::assertContains('RENAME TABLE recurringinvoice_contact TO recurringinvoice_contacts', $sql);
        $this->assertNoDropOrCreateTable($sql);
    }

    public function testUpIsANoOpWhenThePluralTableAlreadyExists(): void
    {
        self::assertSame([], $this->upStatements(new MariaDBPlatform(), $this->schemaWithNewTable()));
    }

    public function testUpIsANoOpWhenNeitherTableExists(): void
    {
        self::assertSame([], $this->upStatements(new MariaDBPlatform(), new Schema()));
    }

    public function testDownRenamesTheTableBackOnPostgreSQLWithoutDroppingOrCreatingIt(): void
    {
        $sql = $this->downStatements(new PostgreSQLPlatform(), $this->schemaWithNewTable());

        self::assertContains('ALTER TABLE recurringinvoice_contacts RENAME TO recurringinvoice_contact', $sql);
        $this->assertNoDropOrCreateTable($sql);
    }

    public function testDownRenamesTheTableBackOnMariaDbWithoutDroppingOrCreatingIt(): void
    {
        $sql = $this->downStatements(new MariaDBPlatform(), $this->schemaWithNewTable());

        self::assertContains('RENAME TABLE recurringinvoice_contacts TO recurringinvoice_contact', $sql);
        $this->assertNoDropOrCreateTable($sql);
    }

    public function testDownIsANoOpWhenTheSingularTableAlreadyExists(): void
    {
        self::assertSame([], $this->downStatements(new MariaDBPlatform(), $this->schemaWithOldTable()));
    }

    /**
     * @param list<string> $sql
     */
    private function assertNoDropOrCreateTable(array $sql): void
    {
        foreach ($sql as $statement) {
            self::assertStringNotContainsStringIgnoringCase('DROP TABLE', $statement);
            self::assertStringNotContainsStringIgnoringCase('CREATE TABLE', $statement);
        }
    }

    /**
     * @return list<string>
     */
    private function upStatements(AbstractPlatform $platform, Schema $schema): array
    {
        $migration = $this->migration($platform);
        $migration->up($schema);

        return $this->sqlStrings($migration);
    }

    /**
     * @return list<string>
     */
    private function downStatements(AbstractPlatform $platform, Schema $schema): array
    {
        $migration = $this->migration($platform);
        $migration->down($schema);

        return $this->sqlStrings($migration);
    }

    /**
     * @return list<string>
     */
    private function sqlStrings(Version30100_21 $migration): array
    {
        return array_map(static fn ($query): string => (string) $query, $migration->getSql());
    }

    private function schemaWithOldTable(): Schema
    {
        $schema = new Schema();
        $schema->createTable('recurringinvoice_contact');

        return $schema;
    }

    private function schemaWithNewTable(): Schema
    {
        $schema = new Schema();
        $schema->createTable('recurringinvoice_contacts');

        return $schema;
    }

    private function migration(AbstractPlatform $platform): Version30100_21
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn($platform);
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));

        return new Version30100_21($connection, new NullLogger());
    }
}
