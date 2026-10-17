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

namespace SolidInvoice\InstallBundle\Tests\Installer\Database;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
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
use SolidInvoice\CoreBundle\Doctrine\Migrations\NaturalVersionComparator;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Doctrine\Type\ObjectType;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use function bin2hex;
use function dirname;
use function getenv;
use function random_bytes;
use function sprintf;

/**
 * `Migration::migrate()` is the install wizard's and the in-app upgrade's only entry point
 * into the real migration chain (see {@see Migration}), so it is proved here directly rather
 * than through `bin/console doctrine:migrations:migrate` - a regression back to the
 * stamp-and-rebuild-from-ORM-metadata approach this class replaces would still leave that
 * console command working correctly and this test is what would catch it.
 *
 * Needs a real MySQL, MariaDB or PostgreSQL server (the same SOLIDINVOICE_DATABASE_URL
 * every other functional test uses): SQLite ignores the dropped/added columns these
 * migrations exist to prove, and the ULID/UUID custom types below are normally registered
 * by the booted kernel's DBAL configuration, which this standalone harness does not have.
 */
#[CoversClass(Migration::class)]
final class MigrationTest extends TestCase
{
    /**
     * The version right before the tax rescue (Version30000_9) and the line position/name
     * backfills (Version30100_5, Version30100_6) - migrating only this far reproduces the
     * schema an upgraded-but-not-yet-migrated 3.0.x install is actually in.
     */
    private const string BASELINE_VERSION = Version30000_8::class;

    /** @var array<string, class-string> */
    private const array CUSTOM_TYPES = [
        'uuid' => UuidType::class,
        'ulid' => UlidType::class,
        BigIntegerType::NAME => BigIntegerType::class,
        QuantityType::NAME => QuantityType::class,
        ObjectType::NAME => ObjectType::class,
    ];

    /** @var array{driver: string, host: string, port: int, user: string, password: string} */
    private array $adminParams;

    private string $databaseName;

    private Connection $connection;

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

        foreach (self::CUSTOM_TYPES as $name => $class) {
            if (! Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $this->databaseName = sprintf('sol173_migration_%s', bin2hex(random_bytes(4)));

        try {
            $admin = $this->administrativeConnection();
            $admin->executeStatement('CREATE DATABASE ' . $admin->getDatabasePlatform()->quoteSingleIdentifier($this->databaseName));
            $admin->close();
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('The configured database is not reachable: %s', $e->getMessage()));
        }

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
    }

    public function testMigrateRescuesClientVatNumberIntoTaxIdentifierInsteadOfJustDroppingIt(): void
    {
        $this->migrateToBaseline();

        $companyId = new Ulid();
        $clientId = new Ulid();

        $this->connection->insert('companies', [
            'id' => $companyId,
            'name' => 'Acme Inc',
        ], ['id' => 'ulid']);

        $this->connection->insert('clients', [
            'id' => $clientId,
            'company_id' => $companyId,
            'name' => 'Acme Client',
            'status' => 'active',
            'vat_number' => 'GB123456789',
            'created' => '2024-01-01 00:00:00',
            'updated' => '2024-01-01 00:00:00',
        ], ['id' => 'ulid', 'company_id' => 'ulid']);

        foreach ($this->migrate() as $_) {
        }

        $schema = $this->connection->createSchemaManager()->introspectSchema();
        self::assertFalse($schema->getTable('clients')->hasColumn('vat_number'), 'vat_number must still be dropped from clients.');

        $rescued = $this->connection->fetchAssociative(
            'SELECT label, value FROM tax_identifier WHERE client_id = ?',
            [$clientId],
            ['ulid'],
        );

        self::assertNotFalse($rescued, 'The VAT number must be rescued into tax_identifier, not lost with the column.');
        self::assertSame('VAT', $rescued['label']);
        self::assertSame('GB123456789', $rescued['value']);
    }

    public function testMigrateBackfillsInvoiceLinePositionsAndNames(): void
    {
        $this->migrateToBaseline();

        $companyId = new Ulid();
        $clientId = new Ulid();
        $invoiceId = new Ulid();

        $this->connection->insert('companies', ['id' => $companyId, 'name' => 'Acme Inc'], ['id' => 'ulid']);

        $this->connection->insert('clients', [
            'id' => $clientId,
            'company_id' => $companyId,
            'name' => 'Acme Client',
            'status' => 'active',
            'created' => '2024-01-01 00:00:00',
            'updated' => '2024-01-01 00:00:00',
        ], ['id' => 'ulid', 'company_id' => 'ulid']);

        $this->connection->insert('invoices', [
            'id' => $invoiceId,
            'company_id' => $companyId,
            'client_id' => $clientId,
            'uuid' => new Uuid('11111111-1111-1111-1111-111111111111'),
            'invoice_id' => 'INV-00001',
            'status' => 'draft',
            'total_amount' => 0,
            'baseTotal_amount' => 0,
            'balance_amount' => 0,
            'tax_amount' => 0,
            'discount_valueMoney_amount' => 0,
            'created' => '2024-01-01 00:00:00',
            'updated' => '2024-01-01 00:00:00',
        ], ['id' => 'ulid', 'company_id' => 'ulid', 'client_id' => 'ulid', 'uuid' => 'uuid']);

        // Inserted in the order they must come out numbered 0..n-1 in: ULIDs are
        // monotonically increasing, so inserting them in this order is what the pre-position
        // schema already relied on for "existing order" (see Version30100_5).
        $lineIds = [new Ulid(), new Ulid(), new Ulid()];
        $descriptions = ['First line', 'Second line', 'Third line'];

        foreach ($lineIds as $i => $lineId) {
            $this->connection->insert('invoice_lines', [
                'id' => $lineId,
                'company_id' => $companyId,
                'invoice_id' => $invoiceId,
                'description' => $descriptions[$i],
                'qty' => 1.0,
                'price_amount' => 1000,
                'total_amount' => 1000,
                'created' => '2024-01-01 00:00:00',
                'updated' => '2024-01-01 00:00:00',
            ], ['id' => 'ulid', 'company_id' => 'ulid', 'invoice_id' => 'ulid']);
        }

        foreach ($this->migrate() as $_) {
        }

        foreach ($lineIds as $i => $lineId) {
            $row = $this->connection->fetchAssociative(
                'SELECT position, name FROM invoice_lines WHERE id = ?',
                [$lineId],
                ['ulid'],
            );

            self::assertNotFalse($row);
            self::assertSame($i, (int) $row['position'], sprintf('Line %d must be numbered %d.', $i, $i));
            self::assertNotSame('', $row['name'], sprintf('Line %d must have a non-empty name backfilled from its description.', $i));
            self::assertSame($descriptions[$i], $row['name']);
        }
    }

    public function testASecondMigrateCallIsANoOpRatherThanARepeatedBackfill(): void
    {
        $this->migrateToBaseline();

        foreach ($this->migrate() as $_) {
        }

        $executedAfterFirstRun = $this->connection->fetchOne('SELECT COUNT(*) FROM migration_versions');

        $messages = [];
        foreach ($this->migrate(static function (string $message) use (&$messages) {
            $messages[] = $message;

            return [];
        }) as $_) {
        }

        self::assertSame(
            (int) $executedAfterFirstRun,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM migration_versions'),
            'A second migrate() call must not record (or re-run) any migration again.',
        );
        self::assertSame(['Database schema is already up to date.'], $messages);
    }

    private function migrateToBaseline(): void
    {
        $dependencyFactory = $this->buildDependencyFactory();
        $dependencyFactory->getMetadataStorage()->ensureInitialized();

        $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion(new Version(self::BASELINE_VERSION));
        $dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration());
    }

    /**
     * @return iterable<string>
     */
    private function migrate(?callable $callback = null): iterable
    {
        return new Migration($this->buildDependencyFactory())->migrate($callback);
    }

    private function buildDependencyFactory(): DependencyFactory
    {
        $configurationLoader = new ConfigurationArray([
            'migrations_paths' => ['DoctrineMigrations' => dirname(__DIR__, 5) . '/migrations'],
            'table_storage' => ['table_name' => 'migration_versions'],
        ]);

        $dependencyFactory = DependencyFactory::fromConnection(
            $configurationLoader,
            new ExistingConnection($this->connection),
            new NullLogger(),
        );
        $dependencyFactory->setService(Comparator::class, new NaturalVersionComparator());

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
