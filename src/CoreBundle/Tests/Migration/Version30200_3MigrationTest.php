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

use DoctrineMigrations\Version30200_2;
use DoctrineMigrations\Version30200_3;

final class Version30200_3MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30200_3::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30200_2::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            // The selector index the platform's ResetPasswordRequest maps.
            'IDX_7CE748A9692E25D',
        ];
    }
}
