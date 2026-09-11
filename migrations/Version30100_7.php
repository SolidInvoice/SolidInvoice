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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Enum\UnitCode;

/**
 * `Version30100_6` belongs to PR 2 of the same stack (GH #2706), which this branch is based on.
 */
final class Version30100_7 extends AbstractMigration
{
    /**
     * Both line tables. `invoice_lines` carries recurring invoice lines too — it is a
     * single-table hierarchy — so two of the three collections are covered by one column.
     */
    private const array TABLES = ['invoice_lines', 'quote_lines'];

    public function getDescription(): string
    {
        return 'Give invoice, recurring invoice and quote lines a unit of measure, defaulting to UN/ECE C62 (unit)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('unit_code')) {
                // No backfill pass: the column default is what every existing row gets, and
                // C62 renders as nothing, so an existing invoice looks exactly as it did.
                $table->addColumn('unit_code', Types::STRING, [
                    'length' => 3,
                    'notnull' => true,
                    'default' => UnitCode::UNIT->value,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('unit_code')) {
                $table->dropColumn('unit_code');
            }
        }
    }
}
