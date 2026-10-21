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

use DoctrineMigrations\Version30102_2;
use DoctrineMigrations\Version30102_3;

/**
 * `recurringinvoice_contact` is a substring of `recurringinvoice_contacts`, so asserting its
 * absence from the drift output also covers the plural form - there is no residual reference
 * to the join table under either name once {@see Version30102_3} has run. The column fragments
 * are the pre-rename snake_case names on reset_password_request and user_invitations.
 */
final class Version30102_3CrossLineageMigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30102_3::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30102_2::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return [
            'recurringinvoice_contact',
            'hashed_token',
            'requested_at',
            'expires_at',
            'reminder_sent_at',
        ];
    }
}
