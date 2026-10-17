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

namespace SolidInvoice\InstallBundle\Tests\Listener;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;
use DoctrineMigrations\Version30000_8;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use SolidInvoice\CoreBundle\Doctrine\Migrations\NaturalVersionComparator;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\InstallBundle\Listener\UpgradeListener;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\DoctrineDbalStore;
use function bin2hex;
use function dirname;
use function file_put_contents;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

/**
 * `UpgradeListener` guards the migration it runs with a lock backed by the database itself
 * (see {@see \SolidInvoice\InstallBundle\Resources\config\services\services.php}), so every
 * host pointed at that database is serialised, not just concurrent requests on one host. These
 * tests build two independent `LockFactory` instances - each with its own `DoctrineDbalStore`
 * and its own DBAL connection to the same database - to stand in for two such hosts.
 *
 * Needs a real MySQL, MariaDB or PostgreSQL server, for the same reason as
 * {@see \SolidInvoice\InstallBundle\Tests\Installer\Database\MigrationTest}: the lock table is
 * real DDL and the migration chain below needs a real engine to replay against.
 */
#[CoversClass(UpgradeListener::class)]
final class UpgradeListenerLockTest extends TestCase
{
    /** @var array{driver: string, host: string, port: int, user: string, password: string} */
    private array $adminParams;

    private string $databaseName;

    private string $databaseUrl;

    private Connection $connection;

    private ?string $abortMigrationDir = null;

    protected function setUp(): void
    {
        $url = $_SERVER['SOLIDINVOICE_DATABASE_URL'] ?? $_ENV['SOLIDINVOICE_DATABASE_URL'] ?? getenv('SOLIDINVOICE_DATABASE_URL');

        if (! $url) {
            self::markTestSkipped('SOLIDINVOICE_DATABASE_URL is not configured.');
        }

        $params = new DsnParser(ConnectionFactory::DEFAULT_SCHEME_MAP)->parse($url);

        if (($params['driver'] ?? null) === 'pdo_sqlite') {
            self::markTestSkipped('This test needs a real MySQL, MariaDB or PostgreSQL server; the configured connection is SQLite.');
        }

        $this->adminParams = [
            'driver' => $params['driver'],
            'host' => $params['host'] ?? '',
            'port' => (int) ($params['port'] ?? 0),
            'user' => $params['user'] ?? '',
            'password' => $params['password'] ?? '',
        ];

        $this->databaseName = sprintf('sol173_lock_%s', bin2hex(random_bytes(4)));

        try {
            $admin = $this->administrativeConnection();
            $admin->executeStatement('CREATE DATABASE ' . $admin->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName));
            $admin->close();
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('The configured database is not reachable: %s', $e->getMessage()));
        }

        $this->databaseUrl = sprintf(
            '%s://%s:%s@%s:%d/%s',
            $this->adminParams['driver'] === 'pdo_pgsql' ? 'postgresql' : 'mysql',
            $this->adminParams['user'],
            $this->adminParams['password'],
            $this->adminParams['host'],
            $this->adminParams['port'],
            $this->databaseName,
        );

        $connectionParams = $this->adminParams;
        $connectionParams['dbname'] = $this->databaseName;
        $this->connection = DriverManager::getConnection($connectionParams);
    }

    protected function tearDown(): void
    {
        if (! isset($this->connection)) {
            // setUp() skipped before opening a connection (no real engine configured).
            return;
        }

        $this->connection->close();

        $admin = $this->administrativeConnection();
        $admin->executeStatement('DROP DATABASE IF EXISTS ' . $admin->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName));
        $admin->close();

        if ($this->abortMigrationDir !== null) {
            new Filesystem()->remove($this->abortMigrationDir);
        }
    }

    public function testTheGuardHoldsAcrossTwoConnections(): void
    {
        $resource = $this->lockResourceName();

        // Stands in for a second host already migrating: a lock taken directly from its own
        // connection, before the listener under test ever gets a chance to acquire it.
        $otherHostLock = $this->buildLockFactory()->createLock($resource, 300.0);
        self::assertTrue($otherHostLock->acquire(false));

        $listener = new UpgradeListener('2024-01-01 00:00:00', $this->buildMigrationToBaseline(), $this->buildLockFactory());

        // buildMigrationToBaseline() already ran the chain up to Version30000_8 to set up a
        // pending migration (Version30000_9 onwards) for isUpToDate() to see; this is the
        // count that must stay unchanged while the "other host" holds the lock.
        $beforeBlockedAttempt = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_versions');

        // The migration is pending (isUpToDate() is false), but the resource is held by the
        // "other host", so this request must fall through without migrating.
        $listener->onKernelRequest($this->mainRequestEvent());

        self::assertSame(
            $beforeBlockedAttempt,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_versions'),
            'A request that cannot acquire the lock must not run the migration.',
        );

        $otherHostLock->release();

        $listener->onKernelRequest($this->mainRequestEvent());

        self::assertGreaterThan(
            $beforeBlockedAttempt,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_versions'),
            'Once the lock is free, the next request must run the migration.',
        );
    }

    public function testTheLockIsNotLeftHeldAfterAnAbort(): void
    {
        $resource = $this->lockResourceName();

        $listener = new UpgradeListener('2024-01-01 00:00:00', $this->buildAbortingMigration(), $this->buildLockFactory());

        try {
            $listener->onKernelRequest($this->mainRequestEvent());
            self::fail('The migration failure must propagate.');
        } catch (RuntimeException $e) {
            self::assertSame('Simulated migration failure.', $e->getMessage());
        }

        self::assertTrue(
            $this->buildLockFactory()->createLock($resource, 300.0)->acquire(false),
            'The lock must be released even when the migration aborts.',
        );
    }

    private function mainRequestEvent(): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
        );
    }

    private function lockResourceName(): string
    {
        return (string) new ReflectionClass(UpgradeListener::class)->getConstant('LOCK_RESOURCE');
    }

    private function buildLockFactory(): LockFactory
    {
        return new LockFactory(new DoctrineDbalStore($this->databaseUrl));
    }

    /**
     * A real {@see Migration}, pointed at the real migrations/ chain, migrated only as far as
     * `Version30000_8` - leaving one real migration (`Version30000_9`) still pending so
     * `isUpToDate()` is false and the next `migrate()` call has genuine work to do.
     */
    private function buildMigrationToBaseline(): Migration
    {
        $dependencyFactory = $this->buildDependencyFactory();
        $dependencyFactory->getMetadataStorage()->ensureInitialized();

        return new Migration($dependencyFactory);
    }

    /**
     * A real {@see Migration} pointed at a throwaway one-migration chain whose `up()` always
     * throws, so the abort happens inside the `foreach` in
     * {@see UpgradeListener::migrateUnderLock()} rather than at `isUpToDate()`, which is what
     * proves the `finally` releases the lock rather than the lock never being taken at all.
     */
    private function buildAbortingMigration(): Migration
    {
        $this->abortMigrationDir = sprintf('%s/sol173_abort_%s', sys_get_temp_dir(), bin2hex(random_bytes(4)));
        new Filesystem()->mkdir($this->abortMigrationDir);

        file_put_contents($this->abortMigrationDir . '/VersionSol173AbortTest.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace DoctrineMigrations;

            use Doctrine\DBAL\Schema\Schema;
            use Doctrine\Migrations\AbstractMigration;
            use RuntimeException;

            final class VersionSol173AbortTest extends AbstractMigration
            {
                public function up(Schema $schema): void
                {
                    throw new RuntimeException('Simulated migration failure.');
                }
            }
            PHP);

        $dependencyFactory = DependencyFactory::fromConnection(
            new ConfigurationArray([
                'migrations_paths' => ['DoctrineMigrations' => $this->abortMigrationDir],
                'table_storage' => ['table_name' => 'migration_versions'],
            ]),
            new ExistingConnection($this->connection),
            new NullLogger(),
        );
        $dependencyFactory->setService(Comparator::class, new NaturalVersionComparator());
        $dependencyFactory->getMetadataStorage()->ensureInitialized();

        return new Migration($dependencyFactory);
    }

    private function buildDependencyFactory(): DependencyFactory
    {
        $configurationLoader = new ConfigurationArray([
            'migrations_paths' => ['DoctrineMigrations' => dirname(__DIR__, 4) . '/migrations'],
            'table_storage' => ['table_name' => 'migration_versions'],
        ]);

        $dependencyFactory = DependencyFactory::fromConnection(
            $configurationLoader,
            new ExistingConnection($this->connection),
            new NullLogger(),
        );
        $dependencyFactory->setService(Comparator::class, new NaturalVersionComparator());
        $dependencyFactory->getMetadataStorage()->ensureInitialized();

        $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion(
            new Version(Version30000_8::class),
        );
        $dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration());

        return $dependencyFactory;
    }

    private function administrativeConnection(): Connection
    {
        $params = $this->adminParams;

        if ($params['driver'] === 'pdo_pgsql') {
            $params['dbname'] = 'postgres';
        }

        return DriverManager::getConnection($params);
    }
}
