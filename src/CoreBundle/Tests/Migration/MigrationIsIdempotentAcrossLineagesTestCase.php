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
use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use function array_merge;
use function dirname;
use function getenv;
use function preg_replace;
use function sprintf;
use function strtolower;

/**
 * There are two schema lineages in the field: a fresh install, built straight from the ORM
 * mapping by the web installer, and an upgraded install, built by replaying every migration
 * in order. A migration that only works against one of them breaks the other, so every
 * migration this test case covers is proved against both, on real MariaDB and PostgreSQL
 * servers rather than SQLite, because platform-specific DDL (identity columns, timestamptz,
 * the MariaDB/MySQL platform split) does not show up against SQLite at all.
 *
 * A concrete test extends this class for one migration and says what "the drift that
 * migration fixes" and "its version identifier" are. Everything else — building both
 * lineages, running the migration through the real `bin/console`, and skipping cleanly when
 * an engine is not reachable — lives here so the next migration in this group only has to
 * add a few lines.
 */
abstract class MigrationIsIdempotentAcrossLineagesTestCase extends TestCase
{
    /**
     * @return class-string the migration under test, e.g. \DoctrineMigrations\Version30100_20::class.
     *                       This string doubles as the migrations version identifier.
     */
    abstract protected static function migrationClass(): string;

    /**
     * Fragments of SQL that `doctrine:schema:update --dump-sql` must no longer report once
     * this migration has run against the upgraded-install lineage.
     *
     * @return list<string>
     */
    abstract protected static function resolvedDriftFragments(): array;

    /**
     * @return iterable<string, array{string, string, int, string, string, string}>
     */
    public static function engines(): iterable
    {
        // Credentials match .github/workflows/db-tests.yml. The server version is pinned in
        // the DSN, also matching db-tests.yml: Doctrine\Bundle\DoctrineBundle\ConnectionFactory
        // probes the platform with an empty StaticServerVersionProvider before the real
        // connection is made, purely to pick a charset default, whenever `dbname_suffix` is
        // configured (true in every test-environment connection here). AbstractPostgreSQLDriver
        // throws outright on that empty string; AbstractMySQLDriver silently falls back to a
        // base MySQLPlatform instead, which is the MariaDB/MySQL sibling-class trap. Pinning
        // the version avoids both.
        yield 'MariaDB' => ['mysql', '127.0.0.1', 3306, 'root', 'solidinvoice', '11.8.6-MariaDB'];
        yield 'PostgreSQL' => ['postgresql', '127.0.0.1', 5432, 'postgres', 'solidinvoice', '17.11'];
    }

    #[DataProvider('engines')]
    final public function testMigrationResolvesDriftAgainstTheUpgradedLineage(
        string $scheme,
        string $host,
        int $port,
        string $user,
        string $password,
        string $serverVersion,
    ): void {
        $engine = new Engine($scheme, $host, $port, $user, $password, $serverVersion);
        $databaseName = $this->databaseName('upgraded');

        $this->skipUnlessReachable($engine);
        $this->recreateDatabase($engine, $databaseName);

        try {
            $this->console($engine, $databaseName, ['doctrine:migrations:migrate', '--no-interaction']);
            $dumpSql = $this->console($engine, $databaseName, ['doctrine:schema:update', '--dump-sql']);

            foreach (static::resolvedDriftFragments() as $fragment) {
                self::assertStringNotContainsString(
                    $fragment,
                    $dumpSql,
                    sprintf(
                        '"%s" is still part of the schema drift reported by doctrine:schema:update after %s ran.',
                        $fragment,
                        static::migrationClass(),
                    ),
                );
            }
        } finally {
            $this->dropDatabase($engine, $databaseName);
        }
    }

    #[DataProvider('engines')]
    final public function testMigrationIsANoOpAgainstTheFreshInstallLineage(
        string $scheme,
        string $host,
        int $port,
        string $user,
        string $password,
        string $serverVersion,
    ): void {
        $engine = new Engine($scheme, $host, $port, $user, $password, $serverVersion);
        $databaseName = $this->databaseName('fresh');

        $this->skipUnlessReachable($engine);
        $this->recreateDatabase($engine, $databaseName);

        try {
            // A fresh install never runs a single migration: SchemaTool builds the schema
            // from the current ORM mapping directly (src/InstallBundle/Installer/Database/
            // Migration.php:90) and every migration is marked executed without running.
            // Reproduce that here, except for the migration under test, so it is the one
            // thing that actually runs against this lineage.
            $this->console($engine, $databaseName, ['doctrine:schema:create']);
            $this->console($engine, $databaseName, ['doctrine:migrations:sync-metadata-storage']);
            $this->console($engine, $databaseName, ['doctrine:migrations:version', '--add', '--all', '--no-interaction']);
            $this->console($engine, $databaseName, ['doctrine:migrations:version', static::migrationClass(), '--delete', '--no-interaction']);

            $output = $this->console($engine, $databaseName, ['doctrine:migrations:migrate', '--dry-run', '--no-interaction']);

            // Doctrine\Migrations\Version\DbalExecutor logs this exact warning when a
            // migration's up() produced no SQL diff against the live schema. --dry-run still
            // calls up() against the real introspected schema and still fails loudly if it
            // throws; it just never executes or persists anything, so this lineage is safe
            // to run the real migration against.
            self::assertStringContainsString(
                'did not result in any SQL statements',
                $output,
                sprintf('%s was not a no-op against a fresh-install schema.', static::migrationClass()),
            );
        } finally {
            $this->dropDatabase($engine, $databaseName);
        }
    }

    /**
     * The base name put in the DSN handed to `bin/console`. config/packages/test/doctrine.php
     * appends its own `_test` + TEST_TOKEN suffix to whatever dbname it finds there, so this
     * one must stay unsuffixed - {@see physicalDatabaseName()} is the name actually on disk.
     */
    private function databaseName(string $lineage): string
    {
        $label = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', static::migrationClass()));

        return sprintf('mig_%s_%s', $label, $lineage);
    }

    /**
     * The name `bin/console` (run with SOLIDINVOICE_ENV=test) actually resolves
     * {@see databaseName()} to, once config/packages/test/doctrine.php's dbname_suffix has
     * been applied. This is the name the administrative connection must create and drop.
     */
    private function physicalDatabaseName(string $databaseName): string
    {
        return $databaseName . '_test' . (getenv('TEST_TOKEN') ?: '');
    }

    private function skipUnlessReachable(Engine $engine): void
    {
        try {
            $this->administrativeConnection($engine)->executeQuery('SELECT 1');
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('%s is not reachable: %s', $engine->scheme, $e->getMessage()));
        }
    }

    private function recreateDatabase(Engine $engine, string $databaseName): void
    {
        $connection = $this->administrativeConnection($engine);
        $quotedName = $connection->getDatabasePlatform()->quoteSingleIdentifier($this->physicalDatabaseName($databaseName));

        $connection->executeStatement('DROP DATABASE IF EXISTS ' . $quotedName);
        $connection->executeStatement('CREATE DATABASE ' . $quotedName);
    }

    private function dropDatabase(Engine $engine, string $databaseName): void
    {
        $connection = $this->administrativeConnection($engine);
        $quotedName = $connection->getDatabasePlatform()->quoteSingleIdentifier($this->physicalDatabaseName($databaseName));

        $connection->executeStatement('DROP DATABASE IF EXISTS ' . $quotedName);
    }

    private function administrativeConnection(Engine $engine): Connection
    {
        $params = [
            'driver' => $engine->scheme === 'postgresql' ? 'pdo_pgsql' : 'pdo_mysql',
            'host' => $engine->host,
            'port' => $engine->port,
            'user' => $engine->user,
            'password' => $engine->password,
        ];

        // pdo_pgsql needs a database to connect to; pdo_mysql does not.
        if ($engine->scheme === 'postgresql') {
            $params['dbname'] = 'postgres';
        }

        return DriverManager::getConnection($params);
    }

    /**
     * @param list<string> $arguments
     */
    private function console(Engine $engine, string $databaseName, array $arguments): string
    {
        $process = new Process(
            array_merge([PHP_BINARY, 'bin/console'], $arguments),
            dirname(__DIR__, 4),
            [
                'SOLIDINVOICE_DATABASE_URL' => sprintf(
                    '%s://%s:%s@%s:%d/%s?serverVersion=%s',
                    $engine->scheme,
                    $engine->user,
                    $engine->password,
                    $engine->host,
                    $engine->port,
                    $databaseName,
                    $engine->serverVersion,
                ),
                'SOLIDINVOICE_ENV' => 'test',
                'SOLIDINVOICE_DEBUG' => '0',
                'SOLIDINVOICE_APP_SECRET' => $_SERVER['SOLIDINVOICE_APP_SECRET'] ?? $_ENV['SOLIDINVOICE_APP_SECRET'] ?? '',
                'APP_ENV' => 'test',
                'APP_DEBUG' => '0',
            ],
        );
        $process->setTimeout(120);
        $process->run();

        self::assertTrue(
            $process->isSuccessful(),
            sprintf("%s\n%s\n%s", $process->getCommandLine(), $process->getOutput(), $process->getErrorOutput()),
        );

        return $process->getOutput() . $process->getErrorOutput();
    }
}
