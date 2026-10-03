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
 * Gives an AbstractMigration hook a reliable way to ask "is this a dry run?".
 *
 * `preUp()`, `postUp()`, `preDown()` and `postDown()` run for real even under
 * `doctrine:migrations:migrate --dry-run` — only the SQL queued by `addSql()` and the
 * schema diff are held back. A hook that mutates the database directly through
 * `$this->connection` (an UPDATE, a CHECK constraint swap, anything outside the Schema
 * object it was handed) must guard itself with `isDryRun()`, or a dry run mutates a
 * live database.
 *
 * Where a hook runs after `up()`/`down()` would have reached the database, prefer
 * checking that the resulting state actually landed (see
 * `Version30100_7::postUp()`'s column-existence guard) over this trait: it stays
 * correct even where the dry-run flag failed to propagate for some other reason.
 * Use this trait for hooks that run before that state exists, most commonly `preDown()`.
 */
trait DryRunAwareMigration
{
    protected function isDryRun(): bool
    {
        return MigrationDryRunState::isDryRun();
    }
}
