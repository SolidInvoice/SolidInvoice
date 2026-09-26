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
use function array_keys;

final class Version30100_8 extends AbstractMigration
{
    private const string COLUMN = 'invoice_type_code';

    private const array DEFAULTS = [
        'invoices' => '380',
        'credit_notes' => '381',
    ];

    public function getDescription(): string
    {
        return 'Add the EN 16931 BT-3 document type code to invoices and credit notes';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::DEFAULTS as $tableName => $default) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn(self::COLUMN)) {
                $table->addColumn(self::COLUMN, Types::STRING, [
                    'length' => 4,
                    'notnull' => true,
                    'default' => $default,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::DEFAULTS) as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn(self::COLUMN)) {
                $table->dropColumn(self::COLUMN);
            }
        }
    }
}
