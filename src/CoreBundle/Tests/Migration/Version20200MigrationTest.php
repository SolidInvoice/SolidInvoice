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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use DoctrineMigrations\Version20200;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use function sprintf;

/**
 * `Table::modifyColumn()` rejects a bare `new JsonType()`/`new IntegerType()` with
 * `TypeNotRegistered` as soon as the current DBAL sets it on a real (non-mocked) `Column` —
 * confirmed directly, outside this test, against `vendor/doctrine/dbal`. Types passed to a
 * schema column must come from the type registry, {@see \Doctrine\DBAL\Types\Type::getType()},
 * which is what {@see Version20200} now uses at its three `modifyColumn()` call sites. A real
 * engine replaying the full migration chain from empty also proves this (verified manually
 * against MariaDB 11.8.6 and PostgreSQL 17.11 for this fix); this test pins the same defect at
 * the unit level, without needing a live database.
 */
final class Version20200MigrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const array ALL_TABLES = [
        'app_config',
        'clients',
        'addresses',
        'contact_types',
        'contacts',
        'client_credit',
        'contact_details',
        'invoices',
        'invoice_lines',
        'payment_methods',
        'payments',
        'quotes',
        'quote_lines',
        'recurring_invoices',
        'tax_rates',
        'api_tokens',
        'api_token_history',
    ];

    public function testUpModifiesThePaymentsDetailsColumnAndEveryTableIdWithoutThrowing(): void
    {
        $schema = $this->preUpgradeSchema();

        $this->migration()->up($schema);

        self::assertSame(Type::getType(Types::JSON), $schema->getTable('payments')->getColumn('details')->getType());

        foreach (self::ALL_TABLES as $tableName) {
            self::assertFalse(
                $schema->getTable($tableName)->getColumn('id')->getAutoincrement(),
                sprintf('%s.id should no longer be autoincrement once it is part of the composite company_id key.', $tableName),
            );
        }
    }

    public function testDownRestoresAnAutoincrementIntegerIdWithoutThrowing(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('clients');
        $table->addColumn('id', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('company_id', Types::STRING, ['length' => 26, 'notnull' => false]);
        $table->addIndex(['company_id']);
        $table->setPrimaryKey(['id', 'company_id']);

        // down() unconditionally drops these three tables the migration itself created in up().
        $schema->createTable('companies');
        $schema->createTable('user_company');
        $schema->createTable('user_invitations');

        $migration = $this->migrationWithoutConnectionExpectations();
        $migration->down($schema);

        $column = $schema->getTable('clients')->getColumn('id');
        self::assertSame(Type::getType(Types::INTEGER), $column->getType());
        self::assertTrue($column->getAutoincrement());
        self::assertSame(['id'], $schema->getTable('clients')->getPrimaryKey()?->getColumns());
    }

    private function preUpgradeSchema(): Schema
    {
        $schema = new Schema();

        foreach ([...self::ALL_TABLES, 'users'] as $tableName) {
            $table = $schema->createTable($tableName);
            $table->addColumn('id', Types::INTEGER, ['notnull' => true, 'autoincrement' => true]);
            $table->setPrimaryKey(['id']);
        }

        $schema->getTable('payments')->addColumn('details', Types::TEXT, ['notnull' => false]);
        $schema->getTable('clients')->addColumn('name', Types::STRING, ['length' => 255, 'notnull' => true]);
        $schema->getTable('client_credit')->addColumn('client_id', Types::INTEGER, ['notnull' => true]);
        $schema->getTable('app_config')->addColumn('setting_key', Types::STRING, ['length' => 255, 'notnull' => true]);

        return $schema;
    }

    private function migration(): Version20200
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn(new MariaDBPlatform());
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));
        $connection->shouldReceive('insert')->twice()->andReturn(1);

        return new Version20200($connection, new NullLogger());
    }

    private function migrationWithoutConnectionExpectations(): Version20200
    {
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->andReturn(new MariaDBPlatform());
        $connection->shouldReceive('createSchemaManager')->andReturn(M::mock(AbstractSchemaManager::class));
        $connection->shouldReceive('delete')->twice()->andReturn(0);

        return new Version20200($connection, new NullLogger());
    }
}
