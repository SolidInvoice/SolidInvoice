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

/**
 * Renames the custom-domain setting key from `system/company/custom_domain` to
 * `system/domain/custom_domain` so the field can live under its own top-level
 * "Domain" section in the settings UI rather than nested under "Company".
 *
 * Safe by design: `Company::customDomain` is the source of truth for inbound
 * host resolution. `CompanyRepository::findOneByCustomDomain()` and
 * `HostRoutingListener` both read from the `companies.custom_domain` column,
 * not from this `app_config` row, so renaming the setting key does not affect
 * how inbound requests resolve to a tenant.
 */
final class Version30000_8 extends AbstractMigration
{
    private const OLD_KEY = 'system/company/custom_domain';

    private const NEW_KEY = 'system/domain/custom_domain';

    public function getDescription(): string
    {
        return 'Rename custom_domain setting from system/company to system/domain section';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $this->connection->update(
            'app_config',
            ['setting_key' => self::NEW_KEY],
            ['setting_key' => self::OLD_KEY]
        );
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->update(
            'app_config',
            ['setting_key' => self::OLD_KEY],
            ['setting_key' => self::NEW_KEY]
        );
    }
}
