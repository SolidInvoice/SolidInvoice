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

use DoctrineMigrations\Version30100_24;

final class Version30100_24MigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30100_24::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            // Index renamed to Doctrine's generated name.
            'uniq_companies_custom_domain',
            'uniq_mcp_refresh_token_identifier',
            // Stale composite index dropped.
            'IDX_1483A5E976F5C865',
            // Index replaced with the mapping's column order.
            'UNIQ_4967254D19EB6921979B1AD6',
            // FK the mapping no longer declares.
            'FK_8E225B2E18C491A5',
        ];
    }
}
