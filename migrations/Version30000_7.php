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
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\SaasBundle\Form\Type\CustomDomainType;
use Symfony\Component\Uid\Ulid;
use function getenv;

final class Version30000_7 extends AbstractMigration
{
    private const SETTING_KEY = 'system/company/custom_domain';

    public function getDescription(): string
    {
        return 'Add nullable unique custom_domain column to companies for SaaS custom domain support';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        if (! $table->hasColumn('custom_domain')) {
            $table->addColumn('custom_domain', 'string', [
                'length' => 253,
                'notnull' => false,
            ]);
        }

        if (! $table->hasIndex('uniq_companies_custom_domain')) {
            $table->addUniqueIndex(['custom_domain'], 'uniq_companies_custom_domain');
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        if (! $this->isSaasPlatform()) {
            return;
        }

        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        foreach ($companies as $company) {
            $companyId = $company['id'];

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
                'setting_value' => null,
                'description' => 'Custom domain for this company (leave empty to use the default URL).',
                'field_type' => CustomDomainType::class,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('companies');

        if ($table->hasIndex('uniq_companies_custom_domain')) {
            $table->dropIndex('uniq_companies_custom_domain');
        }

        if ($table->hasColumn('custom_domain')) {
            $table->dropColumn('custom_domain');
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        if (! $this->isSaasPlatform()) {
            return;
        }

        $this->connection->delete('app_config', ['setting_key' => self::SETTING_KEY]);
    }

    private function isSaasPlatform(): bool
    {
        return ($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? getenv('SOLIDINVOICE_PLATFORM')) === 'saas';
    }
}
