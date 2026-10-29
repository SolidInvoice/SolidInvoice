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

final class Version30200_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the enabled flag to companies';
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

        if (! $table->hasColumn('enabled')) {
            $table->addColumn('enabled', Types::BOOLEAN, ['notnull' => true, 'default' => true]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        if ($table->hasColumn('enabled')) {
            $table->dropColumn('enabled');
        }
    }
}
