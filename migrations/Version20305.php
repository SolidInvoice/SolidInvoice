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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20305 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySqlPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $this->setColumnType($schema, 'invoices', 'due', Types::DATETIME_IMMUTABLE);
        $this->setColumnType($schema, 'invoices', 'invoice_date', Types::DATETIME_IMMUTABLE);
        $this->setColumnType($schema, 'quotes', 'due', Types::DATETIME_IMMUTABLE);
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    private function setColumnType(Schema $schema, string $tableName, string $columnName, string $type): void
    {
        $schema->getTable($tableName)
            ->getColumn($columnName)
            ->setType(Type::getType($type));
    }
}
