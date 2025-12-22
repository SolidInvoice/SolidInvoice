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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;

final class Migration
{
    public function __construct(
        private readonly DependencyFactory $migrationDependencyFactory,
        private readonly ManagerRegistry $registry,
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

    public function migrate(?callable $callback = null): \Generator
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

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($tables, true);
        $conn = $em->getConnection();

        if (count($updateSchemaSql) > 0) {
            foreach ($updateSchemaSql as $sql) {
                $conn->executeStatement($sql);

                if (null !== $callback) {
                    yield from $callback($sql);
                }
            }
        } elseif (null !== $callback) {
            yield from $callback('Database schema is already up to date.');
        }

        $now = new DateTimeImmutable();

        foreach ($plan->getItems() as $item) {
            $metadataStorage->complete(new ExecutionResult($item->getVersion(), $item->getDirection(), $now));
        }
    }
}
