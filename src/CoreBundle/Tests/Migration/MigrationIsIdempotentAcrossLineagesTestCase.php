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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use function array_merge;
use function dirname;
use function explode;
use function getenv;
use function http_build_query;
use function preg_replace;
use function rawurlencode;
use function sprintf;
use function strtolower;

/**
 * There are two schema lineages in the field: a fresh install, built straight from the ORM
 * mapping by the web installer, and an upgraded install, built by replaying every migration
 * in order. A migration that only works against one of them breaks the other, so every
 * migration this test case covers is proved against both, on whatever real engine the current
 * test run is configured for (MariaDB, MySQL or PostgreSQL - the same SOLIDINVOICE_DATABASE_URL
 * every other functional test already uses), because platform-specific DDL (identity columns,
 * timestamptz, the MariaDB/MySQL platform split) does not show up against SQLite at all.
 *
 * This deliberately does not open its own connections to a hardcoded set of engines: CI's
 * `DB (...)` job runs the whole suite once per engine/version leg (.github/workflows/
 * db-tests.yml), each leg's `db` service bound to the same host/port regardless of which
 * image is actually running. A fixed "MariaDB is always on :3306" assumption would collide
 * with the MySQL legs using that same port, forcing DBAL to speak the wrong SQL dialect
 * against the real server underneath. Reading the ambient connection, and detecting its real
 * version live, gets every engine in the matrix covered for free instead.
 *
 * A concrete test extends this class for one migration and says what "the drift that
 * migration fixes" and "its version identifier" are. Everything else — building both
 * lineages, running the migration through the real `bin/console`, and skipping cleanly when
 * there is no real engine configured — lives here so the next migration in this group only
 * has to add a few lines.
 */
abstract class MigrationIsIdempotentAcrossLineagesTestCase extends TestCase
{
    private Engine $engine;

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

    protected function setUp(): void
    {
        $url = $_SERVER['SOLIDINVOICE_DATABASE_URL'] ?? $_ENV['SOLIDINVOICE_DATABASE_URL'] ?? getenv('SOLIDINVOICE_DATABASE_URL');

        if (! $url) {
            self::markTestSkipped('SOLIDINVOICE_DATABASE_URL is not configured.');
        }

        $params = (new DsnParser(ConnectionFactory::DEFAULT_SCHEME_MAP))->parse($url);

        if (($params['driver'] ?? null) === 'pdo_sqlite') {
            self::markTestSkipped('This harness needs a real MySQL, MariaDB or PostgreSQL server; the configured connection is SQLite.');
        }

        // The ambient dbname is whatever database the rest of the suite happens to be using
        // (and, under paratest, may not even exist yet) - connecting to probe reachability
        // and the real server version must not depend on it.
        $this->engine = new Engine($params['driver'], $params['host'], (int) $params['port'], $params['user'], $params['password'], '');

        try {
            $probe = $this->administrativeConnection();
            // getServerVersion() includes human-readable, platform-specific trailing text
            // ("17.11 (Debian 17.11-1.pgdg13+2)", "11.8.6-MariaDB-0+deb13u1 from Debian").
            // The parentheses in the Postgres form are fatal once this DSN reaches
            // config/packages/doctrine.php's env('...')->resolve(): resolve: also expands
            // %parameter% placeholders, and the URL-encoded parens round-trip into exactly
            // that shape. DBAL's own version parsing (AbstractPostgreSQLDriver,
            // AbstractMySQLDriver) only ever reads the leading dotted-number/-MariaDB prefix
            // anyway, so cutting at the first space loses nothing it uses.
            $serverVersion = explode(' ', $probe->getServerVersion(), 2)[0];
            $probe->close();
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('The configured database is not reachable: %s', $e->getMessage()));
        }

        $this->engine = new Engine($params['driver'], $params['host'], (int) $params['port'], $params['user'], $params['password'], $serverVersion);
    }

    final public function testMigrationResolvesDriftAgainstTheUpgradedLineage(): void
    {
        $databaseName = $this->databaseName('upgraded');
        $this->recreateDatabase($databaseName);

        try {
            $this->console($databaseName, ['doctrine:migrations:migrate', '--no-interaction']);
            $dumpSql = $this->console($databaseName, ['doctrine:schema:update', '--dump-sql']);

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
            $this->dropDatabase($databaseName);
        }
    }

    final public function testMigrationIsANoOpAgainstTheFreshInstallLineage(): void
    {
        $databaseName = $this->databaseName('fresh');
        $this->recreateDatabase($databaseName);

        try {
            // A fresh install never runs a single migration: SchemaTool builds the schema
            // from the current ORM mapping directly (src/InstallBundle/Installer/Database/
            // Migration.php:90) and every migration is marked executed without running.
            // Reproduce that here, except for the migration under test, so it is the one
            // thing that actually runs against this lineage.
            $this->console($databaseName, ['doctrine:schema:create']);
            $this->console($databaseName, ['doctrine:migrations:sync-metadata-storage']);
            $this->console($databaseName, ['doctrine:migrations:version', '--add', '--all', '--no-interaction']);
            $this->console($databaseName, ['doctrine:migrations:version', static::migrationClass(), '--delete', '--no-interaction']);

            $output = $this->console($databaseName, ['doctrine:migrations:migrate', '--dry-run', '--no-interaction']);

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
            $this->dropDatabase($databaseName);
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

    private function recreateDatabase(string $databaseName): void
    {
        $connection = $this->administrativeConnection();
        $quotedName = $connection->getDatabasePlatform()->quoteSingleIdentifier($this->physicalDatabaseName($databaseName));

        $connection->executeStatement('DROP DATABASE IF EXISTS ' . $quotedName);
        $connection->executeStatement('CREATE DATABASE ' . $quotedName);
    }

    private function dropDatabase(string $databaseName): void
    {
        $connection = $this->administrativeConnection();
        $quotedName = $connection->getDatabasePlatform()->quoteSingleIdentifier($this->physicalDatabaseName($databaseName));

        $connection->executeStatement('DROP DATABASE IF EXISTS ' . $quotedName);
    }

    private function administrativeConnection(): Connection
    {
        $params = [
            'driver' => $this->engine->driver,
            'host' => $this->engine->host,
            'port' => $this->engine->port,
            'user' => $this->engine->user,
            'password' => $this->engine->password,
        ];

        // pdo_pgsql needs a database to connect to; pdo_mysql does not.
        if ($this->engine->driver === 'pdo_pgsql') {
            $params['dbname'] = 'postgres';
        }

        return DriverManager::getConnection($params);
    }

    /**
     * @param list<string> $arguments
     */
    private function console(string $databaseName, array $arguments): string
    {
        $engine = $this->engine;
        $scheme = $engine->driver === 'pdo_pgsql' ? 'postgresql' : 'mysql';

        $process = new Process(
            array_merge([PHP_BINARY, 'bin/console'], $arguments),
            dirname(__DIR__, 4),
            [
                'SOLIDINVOICE_DATABASE_URL' => sprintf(
                    '%s://%s:%s@%s:%d/%s?%s',
                    $scheme,
                    rawurlencode($engine->user),
                    rawurlencode($engine->password),
                    $engine->host,
                    $engine->port,
                    $databaseName,
                    http_build_query(['serverVersion' => $engine->serverVersion]),
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
