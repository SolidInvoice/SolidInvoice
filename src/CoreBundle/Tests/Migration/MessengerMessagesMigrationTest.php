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

use Doctrine\DBAL\Connection;
use DoctrineMigrations\Version30100_7;
use DoctrineMigrations\Version30102_1;
use function sprintf;

final class MessengerMessagesMigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30102_1::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30100_7::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return ['messenger_messages'];
    }

    protected function assertUpgradedLineageSurvived(Connection $connection): void
    {
        $table = $connection->createSchemaManager()->introspectTable('messenger_messages');

        foreach (['id', 'body', 'headers', 'queue_name', 'created_at', 'available_at', 'delivered_at'] as $column) {
            self::assertTrue(
                $table->hasColumn($column),
                sprintf('messenger_messages is missing its "%s" column after %s ran.', $column, self::migrationClass()),
            );
        }
    }
}
