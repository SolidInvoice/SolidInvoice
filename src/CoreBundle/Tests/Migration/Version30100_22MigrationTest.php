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

use DoctrineMigrations\Version30100_22;

final class Version30100_22MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30100_22::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            'ALTER TABLE addresses ALTER created TYPE',
            'ALTER TABLE payments ALTER completed TYPE',
            'ALTER TABLE users ALTER updated TYPE',
        ];
    }
}
