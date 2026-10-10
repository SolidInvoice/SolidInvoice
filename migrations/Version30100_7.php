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
use SolidInvoice\InvoiceBundle\Form\Type\PaymentTermsSettingType;
use Symfony\Component\Uid\Ulid;

final class Version30100_7 extends AbstractMigration
{
    private const array INVOICE_TABLES = ['invoices', 'recurring_invoices'];

    private const string SETTING_KEY = 'invoice/payment_terms';

    public function getDescription(): string
    {
        return 'Add payment terms to invoices, recurring invoices and clients, and a company default setting';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::INVOICE_TABLES as $tableName) {
            $table = $schema->getTable($tableName);

            // Existing invoices become 'custom', which keeps the due date they already have.
            if (! $table->hasColumn('payment_terms')) {
                $table->addColumn('payment_terms', Types::STRING, ['length' => 25, 'notnull' => true, 'default' => 'custom']);
            }
        }

        $clients = $schema->getTable('clients');

        if (! $clients->hasColumn('payment_terms')) {
            $clients->addColumn('payment_terms', Types::STRING, ['length' => 25, 'notnull' => false]);
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

            $this->connection->insert('app_config', [
                'id' => (new Ulid())->toBinary(),
                'company_id' => $companyId,
                'setting_key' => self::SETTING_KEY,
                'setting_value' => 'net_30',
                'description' => 'Default payment terms for new invoices. A client can override this.',
                'field_type' => PaymentTermsSettingType::class,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([...self::INVOICE_TABLES, 'clients'] as $tableName) {
            $table = $schema->getTable($tableName);

            if ($table->hasColumn('payment_terms')) {
                $table->dropColumn('payment_terms');
            }
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
