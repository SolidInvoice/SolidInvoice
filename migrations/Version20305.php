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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use function in_array;

final class Version20305 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $this->setColumnType($schema, 'invoices', 'due', Types::DATETIME_IMMUTABLE);
        $this->setColumnType($schema, 'invoices', 'invoice_date', Types::DATETIME_IMMUTABLE);
        $this->setColumnType($schema, 'quotes', 'due', Types::DATETIME_IMMUTABLE);

        $this->dropCompanyId($schema, 'invoice_contact');
        $this->dropCompanyId($schema, 'recurringinvoice_contact');
        $this->dropCompanyId($schema, 'quote_contact');
    }

    /**
     * Drops the company_id column together with its foreign key.
     *
     * DBAL 4 removes the dependent index when the column is dropped but leaves the
     * foreign key in place, which makes the index drop fail (it is still needed by
     * the constraint). Removing the foreign key first keeps the generated SQL valid.
     *
     * @throws SchemaException
     */
    private function dropCompanyId(Schema $schema, string $tableName): void
    {
        $table = $schema->getTable($tableName);

        foreach ($table->getForeignKeys() as $foreignKey) {
            if (in_array('company_id', $foreignKey->getLocalColumns(), true)) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }

        $table->dropColumn('company_id');
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
