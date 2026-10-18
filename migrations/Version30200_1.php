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
 * Adds the customer reference column used by
 * {@see \SolidInvoice\InvoiceBundle\Entity\BaseInvoice::getReference()} and
 * {@see \SolidInvoice\QuoteBundle\Entity\Quote::getReference()}, and the per-client default
 * used by {@see \SolidInvoice\ClientBundle\Entity\Client::getDefaultReference()}.
 *
 * The columns are nullable with no default and are never backfilled: an existing
 * 3.0.x/3.1.x row simply has no reference until one is typed on it.
 *
 * down() drops the columns and, with them, every reference typed in the meantime.
 * Rolling back immediately after this deploys is safe — nothing has been written yet.
 * Rolling back later discards every reference a user has typed since, which is the data
 * this column exists to keep.
 */
final class Version30200_1 extends AbstractMigration
{
    private const array DOCUMENT_TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    public function getDescription(): string
    {
        return 'Add the customer reference column to invoices, recurring_invoices and quotes, and the per-client default to clients';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::DOCUMENT_TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('reference')) {
                $table->addColumn('reference', 'string', [
                    'notnull' => false,
                    'length' => 255,
                ]);
            }
        }

        $clients = $schema->getTable('clients');

        if (! $clients->hasColumn('default_reference')) {
            $clients->addColumn('default_reference', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::DOCUMENT_TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('reference')) {
                $table->dropColumn('reference');
            }
        }

        $clients = $schema->getTable('clients');

        if ($clients->hasColumn('default_reference')) {
            $clients->dropColumn('default_reference');
        }
    }
}
