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

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Query\Query;

final class Migration
{
    private DependencyFactory $migrationDependencyFactory;

    public function __construct(DependencyFactory $migrationDependencyFactory)
    {
        $this->migrationDependencyFactory = $migrationDependencyFactory;
    }

    /**
     * @return array<string, Query[]>
     */
    public function migrate(): array
    {
        $this->migrationDependencyFactory->getMetadataStorage()->ensureInitialized();

        $planCalculator = $this->migrationDependencyFactory->getMigrationPlanCalculator();

        $version = $this->migrationDependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');

        $plan = $planCalculator->getPlanUntilVersion($version);

        return $this->migrationDependencyFactory->getMigrator()->migrate(
            $plan,
            (new MigratorConfiguration())
                ->setDryRun(false)
                ->setTimeAllQueries(true)
                ->setAllOrNothing(true)
        );
    }
}
