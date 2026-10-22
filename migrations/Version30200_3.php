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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidInvoice\PaymentBundle\Enum\PaymentMeans;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Uid\Ulid;

final class Version30200_3 extends AbstractMigration
{
    private const string SETTING_KEY = 'invoice/payment_instructions/remittance_reference';

    public function getDescription(): string
    {
        return 'Add the payment_instructions table and the company remittance-reference setting';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        if (! $schema->hasTable(PaymentInstruction::TABLE_NAME)) {
            $table = $schema->createTable(PaymentInstruction::TABLE_NAME);

            $table->addColumn('id', UlidType::NAME);
            $table->addColumn('company_id', UlidType::NAME);
            $table->addColumn('label', Types::STRING, ['length' => 64, 'notnull' => true]);
            $table->addColumn('means', Types::STRING, [
                'length' => 32,
                'notnull' => true,
                'default' => PaymentMeans::BankTransfer->value,
            ]);
            $table->addColumn('account_name', Types::STRING, ['length' => 255, 'notnull' => false]);
            $table->addColumn('account_identifier', Types::STRING, ['length' => 34, 'notnull' => false]);
            $table->addColumn('service_provider_identifier', Types::STRING, ['length' => 35, 'notnull' => false]);
            $table->addColumn('bank_name', Types::STRING, ['length' => 255, 'notnull' => false]);
            $table->addColumn('mandate_reference', Types::STRING, ['length' => 35, 'notnull' => false]);
            $table->addColumn('creditor_identifier', Types::STRING, ['length' => 35, 'notnull' => false]);
            $table->addColumn('instructions', Types::TEXT, ['notnull' => false]);
            $table->addColumn('is_default', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $table->addColumn('created', Types::DATETIME_IMMUTABLE);
            $table->addColumn('updated', Types::DATETIME_IMMUTABLE);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['company_id']);
            $table->addUniqueIndex(['company_id', 'label'], 'uniq_payment_instructions_company_label');

            $table->addForeignKeyConstraint(
                'companies',
                ['company_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
            );
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        foreach ($this->connection->fetchFirstColumn('SELECT id FROM companies') as $companyId) {
            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                [$companyId, self::SETTING_KEY]
            );

            if ($exists !== false) {
                continue;
            }

            $this->connection->insert(
                'app_config',
                [
                    'id' => new Ulid(),
                    'company_id' => $companyId,
                    'setting_key' => self::SETTING_KEY,
                    'setting_value' => '{id}',
                    'description' => 'Reference the client should quote when paying. {id} is replaced with the invoice id.',
                    'field_type' => TextType::class,
                ],
                ['id' => UlidType::NAME],
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(PaymentInstruction::TABLE_NAME)) {
            $schema->dropTable(PaymentInstruction::TABLE_NAME);
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => self::SETTING_KEY]);
    }
}
