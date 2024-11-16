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

use DateTimeImmutable;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\ORM\Tools\SchemaTool;

final class Migration
{
    public function __construct(
        private readonly DependencyFactory $migrationDependencyFactory
    ) {
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

    public function migrate(?callable $callback = null): void
    {
        $metadataStorage = $this->migrationDependencyFactory->getMetadataStorage();

        $metadataStorage->ensureInitialized();

        $em = $this->migrationDependencyFactory->getEntityManager();
        $tables = $em->getMetadataFactory()->getAllMetadata();

        $planCalculator = $this->migrationDependencyFactory->getMigrationPlanCalculator();

        $version = $this->migrationDependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');

        $plan = $planCalculator->getPlanUntilVersion($version);

        $schemaTool = new SchemaTool($em);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($tables, true);
        $conn = $em->getConnection();

        foreach ($updateSchemaSql as $sql) {
            $conn->executeStatement($sql);

            if (null !== $callback) {
                $callback($sql);
            }
        }

        $now = new DateTimeImmutable();

        foreach ($plan->getItems() as $item) {
            $metadataStorage->complete(new ExecutionResult($item->getVersion(), $item->getDirection(), $now));
        }
    }
}
