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
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Uid\Ulid;

final class Version30200_9 extends AbstractMigration
{
    private const string COLUMN = 'receipt_sent_at';

    private const string SETTING_KEY = 'payment/receipt/auto_send';

    public function getDescription(): string
    {
        return 'Record when a payment receipt was last sent, and add the automatic receipt setting to every company';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('payments');

        if (! $table->hasColumn(self::COLUMN)) {
            $table->addColumn(self::COLUMN, Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        }

        $companyIds = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('companies')
            ->fetchFirstColumn();

        $existing = $this->connection->createQueryBuilder()
            ->select('company_id')
            ->from('app_config')
            ->where('setting_key = :key')
            ->setParameter('key', self::SETTING_KEY)
            ->fetchFirstColumn();

        foreach (array_diff($companyIds, $existing) as $companyId) {
            $insert = $this->connection->createQueryBuilder()
                ->insert('app_config')
                ->values([
                    'id' => ':id',
                    'company_id' => ':company_id',
                    'setting_key' => ':setting_key',
                    'setting_value' => ':setting_value',
                    'default_value' => ':setting_value',
                    'description' => ':description',
                    'field_type' => ':field_type',
                ])
                // Unqualified, the ULIDs bind as text and PostgreSQL rejects the raw bytes as invalid UTF-8.
                ->setParameter('id', new Ulid(), UlidType::NAME)
                ->setParameter('company_id', $companyId, UlidType::NAME)
                ->setParameter('setting_key', self::SETTING_KEY)
                ->setParameter('setting_value', '1')
                ->setParameter('description', 'settings.description.payment.receipt.auto_send')
                ->setParameter('field_type', CheckboxType::class);

            $this->addSql($insert->getSQL(), $insert->getParameters(), $insert->getParameterTypes());
        }
    }

    /**
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $table = $schema->getTable('payments');

        if ($table->hasColumn(self::COLUMN)) {
            $table->dropColumn(self::COLUMN);
        }

        $this->addSql('DELETE FROM app_config WHERE setting_key = ?', [self::SETTING_KEY]);
    }
}
