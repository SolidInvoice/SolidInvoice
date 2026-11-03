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

use DoctrineMigrations\Version30200_46;
use DoctrineMigrations\Version30200_9;

final class Version30200_46MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30200_46::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30200_9::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            // invoices.period_start and invoices.period_end are added in the same up() call,
            // so MySQL/MariaDB reports both in one comma-joined ALTER TABLE statement instead
            // of PostgreSQL's one statement per column. Each fragment stays contiguous either
            // way; a fragment that also required the table name immediately in front of it
            // would never match the second column's clause on MySQL/MariaDB, and the
            // assertion would pass without proving anything.
            'ADD period_start DATE DEFAULT NULL',
            'ADD period_end DATE DEFAULT NULL',
            "ADD billing_period_mode VARCHAR(25) DEFAULT 'none' NOT NULL",
        ];
    }
}
