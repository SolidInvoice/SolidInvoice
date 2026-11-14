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
use DoctrineMigrations\Version30200_12;
use DoctrineMigrations\Version30200_50;
use function sprintf;

final class ProcessedMessagesMigrationTest extends MigrationIsIdempotentAcrossLineagesTestCase
{
    protected static function migrationClass(): string
    {
        return Version30200_50::class;
    }

    protected static function predecessorVersion(): string
    {
        return Version30200_12::class;
    }

    protected static function resolvedDriftFragments(): array
    {
        return ['processed_messages'];
    }

    protected function assertUpgradedLineageSurvived(Connection $connection): void
    {
        $table = $connection->createSchemaManager()->introspectTable('processed_messages');

        foreach (
            [
                'id',
                'run_id',
                'attempt',
                'message_type',
                'input',
                'description',
                'dispatched_at',
                'received_at',
                'finished_at',
                'wait_time',
                'handle_time',
                'memory_usage',
                'transport',
                'tags',
                'failure_type',
                'failure_message',
                'results',
            ] as $column
        ) {
            self::assertTrue(
                $table->hasColumn($column),
                sprintf('processed_messages is missing its "%s" column after %s ran.', $column, self::migrationClass()),
            );
        }

        foreach (['idx_processed_messages_finished_at', 'idx_processed_messages_run_id'] as $index) {
            self::assertTrue(
                $table->hasIndex($index),
                sprintf('processed_messages is missing its "%s" index after %s ran.', $index, self::migrationClass()),
            );
        }
    }
}
