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

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Migrations\MigrationDryRunListener;
use SolidInvoice\CoreBundle\Doctrine\Migrations\MigrationDryRunState;
use SolidInvoice\CoreBundle\Tests\Migration\Fixtures\MutatingPreDownMigrationFixture;

/**
 * Runs a migration with a mutating `preDown()` hook through the real Doctrine\Migrations
 * `Migrator`, the same code path `doctrine:migrations:migrate --dry-run` uses, rather than
 * calling the migration's methods directly. This is the only way to prove the guard actually
 * works: `MigrationDryRunListener` only ever sees the dry-run flag through the events that path
 * dispatches.
 *
 * @see \SolidInvoice\CoreBundle\Doctrine\Migrations\DryRunAwareMigration
 * @see \SolidInvoice\CoreBundle\Doctrine\Migrations\MigrationDryRunListener
 */
final class MigrationDryRunGuardTest extends TestCase
{
    private Connection $connection;

    private DependencyFactory $dependencyFactory;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $eventManager = new EventManager();
        $eventManager->addEventListener(
            [Events::onMigrationsMigrating, Events::onMigrationsMigrated],
            new MigrationDryRunListener(),
        );

        $this->dependencyFactory = DependencyFactory::fromConnection(
            new ConfigurationArray(['migrations' => [MutatingPreDownMigrationFixture::class]]),
            new ExistingConnection($this->connection),
        );

        // The listener has to sit on the exact EventManager the executor dispatches through.
        // DependencyFactory only reuses an app's shared EventManager when it is wired to an
        // EntityManager (see DependencyFactory::getEventManager()); overriding the
        // EventDispatcher service directly is the supported way to reach it without one.
        $this->dependencyFactory->setService(
            EventDispatcher::class,
            new EventDispatcher($this->connection, $eventManager),
        );

        $this->dependencyFactory->getMetadataStorage()->ensureInitialized();
    }

    protected function tearDown(): void
    {
        // The real listener resets this on `onMigrationsMigrated`, which never fires if a
        // migration throws — reset unconditionally so a failure here cannot leak into another test.
        MigrationDryRunState::set(false);
    }

    public function testDryRunDownNeitherMutatesTheRowNorDropsTheColumn(): void
    {
        $this->migrate(Direction::UP, false);
        $this->connection->insert(MutatingPreDownMigrationFixture::TABLE_NAME, [
            'id' => 1,
            'value' => 'original',
            'marker' => 'present',
        ]);

        $this->migrate(Direction::DOWN, true);

        self::assertSame(
            'original',
            $this->connection->fetchOne('SELECT value FROM dry_run_guard_fixture WHERE id = 1'),
            'preDown() mutated a live row during a --dry-run down',
        );
        self::assertTrue(
            $this->connection->createSchemaManager()->introspectTable(MutatingPreDownMigrationFixture::TABLE_NAME)->hasColumn('marker'),
            'down() dropped a column for real during a --dry-run down',
        );
    }

    public function testRealDownStillAppliesThePreDownMutationAndTheSchemaChange(): void
    {
        $this->migrate(Direction::UP, false);
        $this->connection->insert(MutatingPreDownMigrationFixture::TABLE_NAME, [
            'id' => 1,
            'value' => 'original',
            'marker' => 'present',
        ]);

        $this->migrate(Direction::DOWN, false);

        self::assertSame(
            'mutated-by-predown',
            $this->connection->fetchOne('SELECT value FROM dry_run_guard_fixture WHERE id = 1'),
        );
        self::assertFalse(
            $this->connection->createSchemaManager()->introspectTable(MutatingPreDownMigrationFixture::TABLE_NAME)->hasColumn('marker'),
        );
    }

    private function migrate(string $direction, bool $dryRun): void
    {
        $plan = $this->dependencyFactory->getMigrationPlanCalculator()->getPlanForVersions(
            [new Version(MutatingPreDownMigrationFixture::class)],
            $direction,
        );

        $this->dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration()->setDryRun($dryRun));
    }
}
