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
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30200_12 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link invoice and quote lines to the product they were copied from';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach ([InvoiceLine::TABLE_NAME, QuoteLine::TABLE_NAME] as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('product_id')) {
                continue;
            }

            $table->addColumn('product_id', UlidType::NAME, ['notnull' => false]);
            $table->addIndex(['product_id']);
            $table->addForeignKeyConstraint(Product::TABLE_NAME, ['product_id'], ['id'], ['onDelete' => 'SET NULL']);
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([InvoiceLine::TABLE_NAME, QuoteLine::TABLE_NAME] as $tableName) {
            $table = $schema->getTable($tableName);

            if (! $table->hasColumn('product_id')) {
                continue;
            }

            foreach ($table->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['product_id']) {
                    $table->removeForeignKey($foreignKey->getName());
                }
            }

            foreach ($table->getIndexes() as $index) {
                if ($index->getColumns() === ['product_id']) {
                    $table->dropIndex($index->getName());
                }
            }

            $table->dropColumn('product_id');
        }
    }
}
