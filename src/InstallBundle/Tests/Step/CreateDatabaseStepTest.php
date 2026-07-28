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

namespace SolidInvoice\InstallBundle\Tests\Step;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Step\CreateDatabaseStep;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use function bin2hex;
use function extension_loaded;
use function getenv;
use function in_array;
use function iterator_to_array;
use function random_bytes;
use function str_contains;
use function sys_get_temp_dir;

#[CoversClass(CreateDatabaseStep::class)]
final class CreateDatabaseStepTest extends TestCase
{
    public function testPriority(): void
    {
        self::assertSame(20, CreateDatabaseStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Creating database', CreateDatabaseStep::getLabel());
    }

    public function testStepImplementsInstallationStepInterface(): void
    {
        self::assertTrue(is_a(CreateDatabaseStep::class, InstallationStepInterface::class, true));
    }

    /**
     * The SQLite branch never touches dbname — it opens the connection so the driver writes the
     * file. Guards the `else` side of the platform check against regressions from the MySQL work.
     */
    public function testCreatesTheDatabaseFileOnSqlite(): void
    {
        $path = sys_get_temp_dir() . '/solidinvoice_create_db_' . bin2hex(random_bytes(6)) . '.db';
        $filesystem = new Filesystem();

        self::assertFileDoesNotExist($path);

        $messages = $this->executeStep(['driver' => 'pdo_sqlite', 'path' => $path]);

        try {
            self::assertFileExists($path, 'The SQLite branch must open the connection so the file is created');
            self::assertContains(sprintf('Database "%s" created', $path), $messages);
        } finally {
            $filesystem->remove($path);
        }
    }

    /**
     * The database being installed into does not exist yet, so the step connects somewhere else to
     * issue the CREATE. DBAL 4's MySQL schema manager resolves its metadata provider from the
     * connection's database and throws DatabaseRequired when there is none, so dropping dbname
     * outright made installing on MySQL and MariaDB fail before anything could be created.
     *
     * This needs a real server to mean anything: without one, both the broken and the fixed
     * parameters fail identically with a connection error, long before the metadata provider is
     * reached. It therefore runs against whichever server the suite is pointed at — every MySQL and
     * MariaDB entry of the db-tests matrix — and skips elsewhere.
     */
    #[DataProvider('mysqlDriverProvider')]
    public function testCreatesTheDatabaseOnAMysqlCompatibleServer(string $driver): void
    {
        $params = $this->mysqlParamsOrSkip($driver);
        $database = 'solidinvoice_create_db_' . bin2hex(random_bytes(6));

        $server = DriverManager::getConnection(['dbname' => 'information_schema'] + $params);

        self::assertNotContains($database, $server->createSchemaManager()->listDatabases());

        try {
            $messages = $this->executeStep(['dbname' => $database] + $params);

            self::assertContains(
                $database,
                $server->createSchemaManager()->listDatabases(),
                'The step must create the database it was pointed at'
            );
            self::assertContains(sprintf('Database "%s" created', $database), $messages);
        } finally {
            // Conditional so a failure inside the try block surfaces as itself, rather than being
            // replaced by a "can't drop database" error from cleaning up something never created.
            if (in_array($database, $server->createSchemaManager()->listDatabases(), true)) {
                $server->createSchemaManager()->dropDatabase($database);
            }
        }
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function mysqlDriverProvider(): Generator
    {
        yield 'pdo_mysql' => ['pdo_mysql'];
        yield 'mysqli' => ['mysqli'];
    }

    /**
     * @param array<string, mixed> $params
     * @return list<string>
     */
    private function executeStep(array $params): array
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getConnection')
            ->willReturn(DriverManager::getConnection($params));

        $messages = [];

        $callback = static function (string $message) use (&$messages): Generator {
            $messages[] = $message;
            yield;
        };

        iterator_to_array(new CreateDatabaseStep($registry, __DIR__)->execute(new Installation(), $callback));

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    private function mysqlParamsOrSkip(string $driver): array
    {
        if (! extension_loaded($driver === 'mysqli' ? 'mysqli' : 'pdo_mysql')) {
            self::markTestSkipped(sprintf('The %s extension is not loaded', $driver));
        }

        $dsn = getenv('SOLIDINVOICE_DATABASE_URL');

        if ($dsn === false || $dsn === '' || ! str_contains($dsn, 'mysql')) {
            self::markTestSkipped('SOLIDINVOICE_DATABASE_URL does not point at a MySQL compatible server');
        }

        $params = new DsnParser(['mysql' => 'pdo_mysql'])->parse($dsn);
        unset($params['dbname']);
        $params['driver'] = $driver;

        try {
            DriverManager::getConnection(['dbname' => 'information_schema'] + $params)
                ->createSchemaManager()
                ->listDatabases();
        } catch (Throwable $e) {
            self::markTestSkipped('No reachable MySQL compatible server: ' . $e->getMessage());
        }

        return $params;
    }
}
