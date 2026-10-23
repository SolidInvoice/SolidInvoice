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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\InvoiceBundle\Enum\BillingPeriodMode;

final class Version30200_5 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the invoicing period (EN 16931 BT-73/BT-74) to invoices, and the period derivation mode to recurring invoices';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $invoices = $schema->getTable('invoices');

        if (! $invoices->hasColumn('period_start')) {
            $invoices->addColumn('period_start', Types::DATE_IMMUTABLE, ['notnull' => false]);
        }

        if (! $invoices->hasColumn('period_end')) {
            $invoices->addColumn('period_end', Types::DATE_IMMUTABLE, ['notnull' => false]);
        }

        $recurring = $schema->getTable('recurring_invoices');

        if (! $recurring->hasColumn('billing_period_mode')) {
            // 'none' keeps every existing recurring invoice generating with no period,
            // identical to its behaviour before this column existed.
            $recurring->addColumn('billing_period_mode', Types::STRING, [
                'length' => 25,
                'notnull' => true,
                'default' => BillingPeriodMode::None->value,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $invoices = $schema->getTable('invoices');

        if ($invoices->hasColumn('period_start')) {
            $invoices->dropColumn('period_start');
        }

        if ($invoices->hasColumn('period_end')) {
            $invoices->dropColumn('period_end');
        }

        $recurring = $schema->getTable('recurring_invoices');

        if ($recurring->hasColumn('billing_period_mode')) {
            $recurring->dropColumn('billing_period_mode');
        }
    }
}
