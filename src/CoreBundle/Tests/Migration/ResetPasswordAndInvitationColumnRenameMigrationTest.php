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

use DoctrineMigrations\Version30100_23;

final class ResetPasswordAndInvitationColumnRenameMigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30100_23::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            'hashed_token',
            'requested_at',
            'expires_at',
            'reminder_sent_at',
        ];
    }
}
