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

use DoctrineMigrations\Version30100_3;
use DoctrineMigrations\Version30100_4;

final class Version30100_4MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30100_4::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30100_3::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        // Version30100_4 does not leave any outstanding schema drift of its own; this test
        // exists to prove postUp() seeds the app_config row through a platform-aware Ulid
        // binding, instead of a raw toBinary() that only PostgreSQL rejects.
        return [];
    }
}
