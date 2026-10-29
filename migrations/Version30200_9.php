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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Uid\Ulid;

final class Version30200_9 extends AbstractMigration
{
    /**
     * Setting key => [default value, description, form type]. New companies get these from the
     * ConfigProviders; this gives them to the existing ones, so the settings form shows them.
     */
    private const array SETTINGS = [
        'invoice/default_terms' => [null, 'settings.description.invoice.default_terms', TextareaType::class],
        'invoice/default_notes' => [null, 'settings.description.invoice.default_notes', TextareaType::class],
        'quote/default_terms' => [null, 'settings.description.quote.default_terms', TextareaType::class],
        'quote/default_notes' => [null, 'settings.description.quote.default_notes', TextareaType::class],
        'quote/validity_days' => ['30', 'settings.description.quote.validity_days', IntegerType::class],
    ];

    public function getDescription(): string
    {
        return 'Add the default terms, notes and quote validity settings to every company that lacks them';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $companyIds = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('companies')
            ->fetchFirstColumn();

        $existing = [];

        $rows = $this->connection->createQueryBuilder()
            ->select('company_id', 'setting_key')
            ->from('app_config')
            ->where('setting_key IN (:keys)')
            ->setParameter('keys', array_keys(self::SETTINGS), ArrayParameterType::STRING)
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $existing[$row['company_id'] . '|' . $row['setting_key']] = true;
        }

        foreach ($companyIds as $companyId) {
            foreach (self::SETTINGS as $key => [$value, $description, $formType]) {
                if (isset($existing[$companyId . '|' . $key])) {
                    continue;
                }

                $insert = $this->connection->createQueryBuilder()
                    ->insert('app_config')
                    ->values([
                        'id' => ':id',
                        'company_id' => ':company_id',
                        'setting_key' => ':setting_key',
                        'setting_value' => ':setting_value',
                        'description' => ':description',
                        'field_type' => ':field_type',
                    ])
                    // Unqualified, the ULIDs bind as text and PostgreSQL rejects the raw bytes as invalid UTF-8.
                    ->setParameter('id', new Ulid(), UlidType::NAME)
                    ->setParameter('company_id', $companyId, UlidType::NAME)
                    ->setParameter('setting_key', $key)
                    ->setParameter('setting_value', $value)
                    ->setParameter('description', $description)
                    ->setParameter('field_type', $formType);

                $this->addSql($insert->getSQL(), $insert->getParameters(), $insert->getParameterTypes());
            }
        }
    }

    public function down(Schema $schema): void
    {
        $delete = $this->connection->createQueryBuilder()
            ->delete('app_config')
            ->where('setting_key IN (:keys)')
            ->setParameter('keys', array_keys(self::SETTINGS), ArrayParameterType::STRING);

        $this->addSql($delete->getSQL(), $delete->getParameters(), $delete->getParameterTypes());
    }
}
