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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version30100_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom_field and custom_field_value tables; migrate legacy contact_type/additional_contact_detail data; drop legacy tables.';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // 1. Create custom_field
        $cf = $schema->createTable('custom_field');
        $cf->addColumn('id', 'ulid', ['notnull' => true]);
        $cf->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cf->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('label', 'string', ['length' => 125, 'notnull' => true]);
        $cf->addColumn('field_key', 'string', ['length' => 64, 'notnull' => true]);
        $cf->addColumn('type', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('options', 'json', ['notnull' => false]);
        $cf->addColumn('required', 'boolean', ['notnull' => true, 'default' => false]);
        $cf->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $cf->addColumn('created', 'datetime', ['notnull' => true]);
        $cf->addColumn('updated', 'datetime', ['notnull' => true]);
        $cf->setPrimaryKey(['id']);
        $cf->addUniqueIndex(['company_id', 'target', 'field_key'], 'uq_cf_company_target_key');
        $cf->addIndex(['company_id', 'target', 'position'], 'idx_cf_company_target_pos');
        $cf->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 2. Create custom_field_value
        $cfv = $schema->createTable('custom_field_value');
        $cfv->addColumn('id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('field_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cfv->addColumn('target_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('value', 'text', ['notnull' => false]);
        $cfv->addColumn('created', 'datetime', ['notnull' => true]);
        $cfv->addColumn('updated', 'datetime', ['notnull' => true]);
        $cfv->setPrimaryKey(['id']);
        $cfv->addUniqueIndex(['field_id', 'target_id'], 'uq_cfv_field_record');
        $cfv->addIndex(['company_id', 'target', 'target_id'], 'idx_cfv_company_target_record');
        $cfv->addIndex(['field_id'], 'idx_cfv_field');
        $cfv->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $cfv->addForeignKeyConstraint('custom_field', ['field_id'], ['id'], ['onDelete' => 'CASCADE']);

        // Task 7 will append data-copy + legacy table drops here.
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'This migration restructures contact types into a unified custom-field schema. ' .
            'Reversing would lose data. Restore from backup.'
        );
    }
}
