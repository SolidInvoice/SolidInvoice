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

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the frozen `currency_code` column used by
 * {@see \SolidInvoice\InvoiceBundle\Entity\BaseInvoice::getCurrency()} and
 * {@see \SolidInvoice\QuoteBundle\Entity\Quote::getCurrency()} (SOL-298).
 *
 * The column is nullable with no default and is never backfilled: a null value falls
 * back to the client's current currency, which is exactly today's behaviour, so an
 * existing 3.0.x/3.1.x row is unaffected until the document is next created or leaves
 * Draft/New.
 *
 * down() drops the column and, with it, every value it froze. Rolling back immediately
 * after this deploys is safe — nothing has been written yet. Rolling back after
 * documents have been issued in the meantime discards the exact history this column
 * exists to protect: those documents will start restating themselves again, which is
 * the defect this migration fixes in the first place.
 */
final class Version30100_9 extends AbstractMigration
{
    private const array TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    public function getDescription(): string
    {
        return 'Add the frozen currency_code column to invoices, recurring_invoices and quotes';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('currency_code')) {
                $table->addColumn('currency_code', 'string', [
                    'notnull' => false,
                    'length' => 3,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('currency_code')) {
                $table->dropColumn('currency_code');
            }
        }
    }
}
