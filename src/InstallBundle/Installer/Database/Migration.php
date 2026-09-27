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
        $metadataStorage = $this->migrationDependencyFactory->getMetadataStorage();

        $metadataStorage->ensureInitialized();

        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);
        $tables = $em->getMetadataFactory()->getAllMetadata();

        $planCalculator = $this->migrationDependencyFactory->getMigrationPlanCalculator();

        $version = $this->migrationDependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');

        $plan = $planCalculator->getPlanUntilVersion($version);

        $schemaTool = new SchemaTool($em);
        $conn = $em->getConnection();

        // ORM 3's SchemaTool::getUpdateSchemaSql() no longer has a "save mode" (the
        // boolean second argument was removed in ORM 3), so it now emits DROP TABLE
        // statements for any table present in the database but absent from the ORM
        // metadata. Two categories of tables must be excluded from the schema
        // introspection while the update SQL is computed:
        //
        // 1. The migrations metadata table (created by ensureInitialized() above) —
        //    it is not an ORM entity, so without excluding it the generated SQL would
        //    try to drop it.
        //
        // 2. Any other table that exists in the database but is not managed by the
        //    current ORM configuration. The most common case is the `saas_plan` table
        //    (and other SaaS-bundle tables) which are present in the database when the
        //    app was previously run in SaaS mode, but are absent from ORM metadata
        //    when the app is running in non-SaaS mode. Without filtering these out,
        //    getUpdateSchemaSql() would generate DROP TABLE statements for them, which
        //    then fail on SQLite with "no such table" because the table is either
        //    physically absent or has already been removed mid-sequence.
        $dbalConfiguration = $conn->getConfiguration();
        $previousFilter = $dbalConfiguration->getSchemaAssetsFilter();
        $migrationsTable = $this->migrationsTableName();

        // Build the complete set of table names managed by the current ORM: entity
        // primary tables plus join tables from ManyToMany associations.
        $ormTableNames = [];
        foreach ($tables as $classMetadata) {
            $ormTableNames[$classMetadata->getTableName()] = true;
            foreach ($classMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable']['name'])) {
                    $ormTableNames[$mapping['joinTable']['name']] = true;
                }
            }
        }

        $dbalConfiguration->setSchemaAssetsFilter(
            static function (string $assetName) use ($previousFilter, $migrationsTable, $ormTableNames): bool {
                if ($migrationsTable !== null && $assetName === $migrationsTable) {
                    return false;
                }

                // Exclude any DB table not managed by the current ORM configuration.
                // This prevents DROP TABLE from being generated for tables that belong
                // to conditionally-loaded bundles (e.g. SaaS tables in non-SaaS mode).
                if (! isset($ormTableNames[$assetName])) {
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
    }

    private function migrationsTableName(): ?string
    {
        $storageConfiguration = $this->migrationDependencyFactory->getConfiguration()->getMetadataStorageConfiguration();

        return $storageConfiguration instanceof TableMetadataStorageConfiguration
            ? $storageConfiguration->getTableName()
            : null;
    }
}
