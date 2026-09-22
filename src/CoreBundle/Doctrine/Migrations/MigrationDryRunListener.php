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

namespace SolidInvoice\CoreBundle\Doctrine\Migrations;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Events;

/**
 * Captures the dry-run flag for the migration run that is starting, so
 * {@see DryRunAwareMigration} can hand it back to a migration's hooks.
 *
 * `onMigrationsMigrating` fires once per `doctrine:migrations:migrate` or
 * `doctrine:migrations:execute` invocation, before any migration's pre- or post-
 * hook runs, and carries the MigratorConfiguration those hooks never see directly.
 */
#[AsDoctrineListener(Events::onMigrationsMigrating)]
#[AsDoctrineListener(Events::onMigrationsMigrated)]
final class MigrationDryRunListener
{
    public function onMigrationsMigrating(MigrationsEventArgs $args): void
    {
        MigrationDryRunState::set($args->getMigratorConfiguration()->isDryRun());
    }

    public function onMigrationsMigrated(MigrationsEventArgs $args): void
    {
        MigrationDryRunState::set(false);
    }
}
