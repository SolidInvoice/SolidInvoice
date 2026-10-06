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
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as MessengerConnection;

final class Version30100_20 extends AbstractMigration
{
    private const string TABLE_NAME = 'messenger_messages';

    public function getDescription(): string
    {
        return 'Add the messenger_messages table, so the failed transport does not issue DDL at runtime on first send';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        // vendor/symfony/doctrine-messenger/Transport/Connection.php is the authority on the
        // column set for this table, and it has changed across Symfony minors. Building it
        // this way, instead of hand-rolling the DDL, keeps the migration correct when Symfony
        // changes it.
        new MessengerConnection(['table_name' => self::TABLE_NAME], $this->connection)
            ->configureSchema($schema, $this->connection, static fn (): bool => true);
    }

    public function down(Schema $schema): void
    {
        if (! $schema->hasTable(self::TABLE_NAME)) {
            return;
        }

        $schema->dropTable(self::TABLE_NAME);
    }
}
