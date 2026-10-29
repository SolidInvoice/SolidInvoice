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

final class Version30200_11 extends AbstractMigration
{
    private const string TABLE_NAME = 'tax_rates';

    public function getDescription(): string
    {
        return 'Let a company mark one tax rate as the default for new lines';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if (! $table->hasColumn('is_default')) {
            $table->addColumn('is_default', Types::BOOLEAN, ['default' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        if ($table->hasColumn('is_default')) {
            $table->dropColumn('is_default');
        }
    }
}
