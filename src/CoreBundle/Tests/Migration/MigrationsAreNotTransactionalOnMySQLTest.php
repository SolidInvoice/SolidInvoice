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
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use DoctrineMigrations\Version20200;
use DoctrineMigrations\Version20300;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use function basename;
use function dirname;
use function glob;
use function sort;

/**
 * `MariaDBPlatform` is a sibling of `MySQLPlatform`, not a subclass. A migration that
 * tests `instanceof MySQLPlatform` treats MariaDB as transactional DDL, but MariaDB
 * commits implicitly on DDL like MySQL does, so the transaction would be a fiction.
 *
 * This scans every migration under migrations/ that overrides isTransactional(), so a
 * future migration written with the same mistake fails here too.
 */
final class MigrationsAreNotTransactionalOnMySQLTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return iterable<string, array{AbstractPlatform, bool}>
     */
    public static function platforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform(), false];
        yield 'MariaDB' => [new MariaDBPlatform(), false];
        yield 'Oracle' => [new OraclePlatform(), false];
        yield 'PostgreSQL' => [new PostgreSQLPlatform(), true];
    }

    /**
     * @return iterable<string, array{class-string<AbstractMigration>, AbstractPlatform, bool}>
     */
    public static function migrationsAndPlatforms(): iterable
    {
        foreach (self::migrationClasses() as $class) {
            foreach (self::platforms() as $platformLabel => [$platform, $expected]) {
                yield $class . ' on ' . $platformLabel => [$class, $platform, $expected];
            }
        }
    }

    #[DataProvider('migrationsAndPlatforms')]
    public function testIsTransactional(string $migrationClass, AbstractPlatform $platform, bool $expected): void
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn($platform);
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));

        $migration = new $migrationClass($connection, new NullLogger());

        self::assertSame($expected, $migration->isTransactional());
    }

    public function testVersion20200PreUpTogglesForeignKeyChecksOnMariaDb(): void
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn(new MariaDBPlatform());
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));
        $connection->shouldReceive('executeQuery')->once()->with('SET FOREIGN_KEY_CHECKS=0');

        $migration = new Version20200($connection, new NullLogger());
        $migration->preUp(new Schema());
    }

    public function testVersion20300PreUpTogglesForeignKeyChecksOnMariaDb(): void
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn(new MariaDBPlatform());
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));
        $connection->shouldReceive('update')->andReturn(0);
        $connection->shouldReceive('delete')->andReturn(0);
        $connection->shouldReceive('executeQuery')->once()->with('SET FOREIGN_KEY_CHECKS=0;');

        $migration = new Version20300($connection, new NullLogger());
        $migration->preUp(new Schema());
    }

    /**
     * @return list<class-string<AbstractMigration>>
     */
    private static function migrationClasses(): array
    {
        $migrationsDir = dirname(__DIR__, 4) . '/migrations';

        $files = glob($migrationsDir . '/Version*.php');

        if ($files === false) {
            throw new RuntimeException('Unable to list migration files in ' . $migrationsDir);
        }

        $classes = [];

        foreach ($files as $file) {
            /** @var class-string<AbstractMigration> $class */
            $class = 'DoctrineMigrations\\' . basename($file, '.php');

            $reflection = new ReflectionClass($class);

            if ($reflection->getMethod('isTransactional')->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
