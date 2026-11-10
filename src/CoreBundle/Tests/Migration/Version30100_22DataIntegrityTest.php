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

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Tools\DsnParser;
use DoctrineMigrations\Version30100_22;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function bin2hex;
use function getenv;
use function random_bytes;
use function sprintf;

/**
 * {@see Version30100_22MigrationTest} proves the schema ends up right. It does not prove
 * the data survives, which is the actual point of this migration: Doctrine's own schema-diff
 * would emit the `ALTER ... TYPE TIMESTAMP(0) WITH TIME ZONE` with no `USING` clause, and
 * PostgreSQL would then reinterpret every already-stored naive timestamp in the session's
 * `TimeZone` - silently shifting it on any server not running UTC. This runs the migration's
 * real generated SQL, with the session deliberately not on UTC, and checks the stored
 * instant survives.
 */
final class Version30100_22DataIntegrityTest extends TestCase
{
    private const string TABLE = 'sol_380_timezone_safety_check';

    private ?Connection $administrative = null;

    private ?Connection $connection = null;

    private string $databaseName = '';

    protected function setUp(): void
    {
        $url = $_SERVER['SOLIDINVOICE_DATABASE_URL'] ?? $_ENV['SOLIDINVOICE_DATABASE_URL'] ?? getenv('SOLIDINVOICE_DATABASE_URL');

        if (! $url) {
            self::markTestSkipped('SOLIDINVOICE_DATABASE_URL is not configured.');
        }

        $params = new DsnParser(ConnectionFactory::DEFAULT_SCHEME_MAP)->parse($url);

        if (($params['driver'] ?? null) !== 'pdo_pgsql') {
            self::markTestSkipped('This test needs a real PostgreSQL server; the configured connection is not PostgreSQL.');
        }

        $adminParams = $params;
        $adminParams['dbname'] = 'postgres';

        try {
            $this->administrative = DriverManager::getConnection($adminParams);
            $this->administrative->executeStatement('SELECT 1');
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('The configured database is not reachable: %s', $e->getMessage()));
        }

        $this->databaseName = 'sol_380_timezone_safety_' . bin2hex(random_bytes(4));
        $quotedName = $this->administrative->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName);
        $this->administrative->executeStatement('CREATE DATABASE ' . $quotedName);

        $connectionParams = $params;
        $connectionParams['dbname'] = $this->databaseName;
        $this->connection = DriverManager::getConnection($connectionParams);

        $this->connection->executeStatement(sprintf(
            'CREATE TABLE %s (id SERIAL PRIMARY KEY, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL)',
            self::TABLE,
        ));
    }

    protected function tearDown(): void
    {
        $this->connection?->close();

        if ($this->administrative instanceof Connection && $this->databaseName !== '') {
            $quotedName = $this->administrative->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName);
            $this->administrative->executeStatement('DROP DATABASE IF EXISTS ' . $quotedName);
        }

        $this->administrative?->close();
    }

    public function testTheUsingClauseKeepsAStoredInstantUnchangedUnderANonUtcSession(): void
    {
        $connection = $this->connection;

        // UTCDateTimeType always writes the wall-clock UTC reading of an instant - this is
        // what a real INSERT through the ORM would have produced for 2024-06-15 12:00:00 UTC,
        // before this migration (or the ORM's own type) ever touched the column.
        $connection->insert(self::TABLE, [
            'created' => '2024-06-15 12:00:00',
            'updated' => '2024-06-15 12:00:00',
        ]);

        // A server not running UTC - the exact condition the missing USING clause breaks.
        $connection->executeStatement("SET TIME ZONE 'America/New_York'");

        $migration = new Version30100_22($connection, new NullLogger());
        $schema = $connection->createSchemaManager()->introspectSchema();
        $migration->up($schema);

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }

        $storedAsUtc = $connection->fetchOne(sprintf(
            "SELECT created AT TIME ZONE 'UTC' FROM %s",
            self::TABLE,
        ));

        self::assertSame('2024-06-15 12:00:00', $storedAsUtc);
    }
}
