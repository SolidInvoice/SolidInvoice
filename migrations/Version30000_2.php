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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version30000_2 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description field to API tokens for better token management';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $apiTokensTable = $schema->getTable('api_tokens');
        $apiTokensTable->addColumn('description', Types::TEXT, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $apiTokensTable = $schema->getTable('api_tokens');
        $apiTokensTable->dropColumn('description');
    }
}
