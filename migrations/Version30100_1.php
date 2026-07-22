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

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version30100_1 extends AbstractMigration
{
    private const string INDEX_NAME = 'idx_invoice_reminder_scan';

    public function getDescription(): string
    {
        return 'Add a (due, status) index on invoices to support the reminder scans';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('invoices');

        if (! $table->hasIndex(self::INDEX_NAME)) {
            // The reminder and overdue commands scan every tenant at once, so company_id is not a
            // predicate and must not lead. The due date does: it has orders of magnitude more
            // distinct values than status, which in practice is overwhelmingly 'pending'.
            $table->addIndex(['due', 'status'], self::INDEX_NAME);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('invoices');

        if ($table->hasIndex(self::INDEX_NAME)) {
            $table->dropIndex(self::INDEX_NAME);
        }
    }
}
