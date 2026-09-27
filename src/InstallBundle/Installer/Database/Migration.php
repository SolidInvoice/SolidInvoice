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

namespace SolidInvoice\InstallBundle\Installer\Database;

use Carbon\CarbonImmutable;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\SqlFormatter\SqlFormatter;
use Generator;

final readonly class Migration
{
    private SqlFormatter $sqlFormatter;

    public function __construct(
        private DependencyFactory $migrationDependencyFactory,
        private ManagerRegistry $registry,
    ) {
        $this->sqlFormatter = new SqlFormatter();
    }

    public function isUpToDate(): bool
    {
        $statusCalculator = $this->migrationDependencyFactory->getMigrationStatusCalculator();

        $executedUnavailableMigrations = $statusCalculator->getExecutedUnavailableMigrations();
        $newMigrations = $statusCalculator->getNewMigrations();
        $newMigrationsCount = count($newMigrations);
        $executedUnavailableMigrationsCount = count($executedUnavailableMigrations);

        return $newMigrationsCount === 0 && $executedUnavailableMigrationsCount === 0;
    }

    public function migrate(?callable $callback = null): Generator
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);
        $conn = $em->getConnection();

        // Acquire a database-level advisory lock so that only one process
        // (across all app servers) can run the schema migration at a time.
        // A timeout of 60 seconds prevents indefinite blocking if a server
        // crashes while holding the lock.
        $conn->executeQuery("SELECT GET_LOCK('solidinvoice_schema_migration', 60)");

        try {
            // Re-check inside the lock: a concurrent process may have already
            // completed the migration while we were waiting.
            if ($this->isUpToDate()) {
                return;
            }

            $metadataStorage = $this->migrationDependencyFactory->getMetadataStorage();

            $metadataStorage->ensureInitialized();

            $tables = $em->getMetadataFactory()->getAllMetadata();

            $planCalculator = $this->migrationDependencyFactory->getMigrationPlanCalculator();

            $version = $this->migrationDependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');

            $plan = $planCalculator->getPlanUntilVersion($version);

            $schemaTool = new SchemaTool($em);

            // ORM 3's SchemaTool::getUpdateSchemaSql() no longer has a "save mode" (the
            // boolean second argument was removed in ORM 3), so it now emits DROP TABLE
            // statements for any table present in the database but absent from the ORM
            // metadata. The migrations metadata table (created by ensureInitialized()
            // above) is exactly such a table, so without excluding it the generated SQL
            // would try to drop it. Filter it out of the schema introspection while the
            // update SQL is computed.
            $dbalConfiguration = $conn->getConfiguration();
            $previousFilter = $dbalConfiguration->getSchemaAssetsFilter();
            $migrationsTable = $this->migrationsTableName();

            $dbalConfiguration->setSchemaAssetsFilter(
                static function (string $assetName) use ($previousFilter, $migrationsTable): bool {
                    if ($migrationsTable !== null && $assetName === $migrationsTable) {
                        return false;
                    }

                    return $previousFilter($assetName);
                }
            );

            try {
                $updateSchemaSql = $schemaTool->getUpdateSchemaSql($tables);
            } finally {
                $dbalConfiguration->setSchemaAssetsFilter($previousFilter);
            }

            if ($updateSchemaSql !== []) {
                foreach ($updateSchemaSql as $sql) {
                    $conn->executeStatement($sql);

                    if (null !== $callback) {
                        yield from $callback($this->sqlFormatter->format($sql));
                    }
                }
            } elseif (null !== $callback) {
                yield from $callback('Database schema is already up to date.');
            }

            $now = CarbonImmutable::now();

            foreach ($plan->getItems() as $item) {
                $metadataStorage->complete(new ExecutionResult($item->getVersion(), $item->getDirection(), $now));
            }
        } finally {
            $conn->executeQuery("SELECT RELEASE_LOCK('solidinvoice_schema_migration')");
        }
    }

    private function migrationsTableName(): ?string
    {
        $storageConfiguration = $this->migrationDependencyFactory->getConfiguration()->getMetadataStorageConfiguration();

        return $storageConfiguration instanceof TableMetadataStorageConfiguration
            ? $storageConfiguration->getTableName()
            : null;
    }
}
