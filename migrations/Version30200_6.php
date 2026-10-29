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

final class Version30200_6 extends AbstractMigration
{
    private const array COLUMNS = ['created', 'updated'];

    public function getDescription(): string
    {
        return 'Make the company timestamps NOT NULL now that Version30200_5 has backfilled them';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        foreach (self::COLUMNS as $column) {
            $table->getColumn($column)->setNotnull(true);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        foreach (self::COLUMNS as $column) {
            $table->getColumn($column)->setNotnull(false);
        }
    }
}
