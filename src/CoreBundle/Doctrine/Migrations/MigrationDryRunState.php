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

/**
 * Holds the current migration run's dry-run flag so a migration hook can read it.
 *
 * Doctrine\Migrations instantiates migrations with only a Connection and a Logger
 * (see AbstractMigration::__construct()) and never passes the MigratorConfiguration
 * to pre- or post- hooks, so a migration cannot ask "is this a dry run?" through
 * autowiring or a constructor argument. {@see MigrationDryRunListener} is the only
 * place this state is written; it captures the flag from the one event Doctrine\Migrations
 * does carry it on, for the one migration run happening in this process.
 */
final class MigrationDryRunState
{
    private static bool $dryRun = false;

    public static function set(bool $dryRun): void
    {
        self::$dryRun = $dryRun;
    }

    public static function isDryRun(): bool
    {
        return self::$dryRun;
    }
}
