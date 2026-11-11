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
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30200_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the products table, a company-scoped catalogue of items that can be copied onto invoice and quote lines';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(Product::TABLE_NAME)) {
            return;
        }

        $table = $schema->createTable(Product::TABLE_NAME);

        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('company_id', UlidType::NAME);
        $table->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addColumn('description', Types::TEXT, ['notnull' => false]);
        $table->addColumn('price', Types::DECIMAL, ['precision' => 19, 'scale' => 6]);
        $table->addColumn('quantity', Types::DECIMAL, ['precision' => QuantityType::PRECISION, 'scale' => QuantityType::SCALE]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id']);
        $table->addIndex(['tax_id']);

        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint(Tax::TABLE_NAME, ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(Product::TABLE_NAME)) {
            $schema->dropTable(Product::TABLE_NAME);
        }
    }
}
