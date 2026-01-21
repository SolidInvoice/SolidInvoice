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

use const JSON_THROW_ON_ERROR;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use JsonException;
use function json_encode;

final class Version30000_3 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add form_options and default_value columns to app_config table for trial-restricted settings support';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $configTable = $schema->getTable('app_config');

        // Add form_options column for storing form field options like trial_restricted
        $configTable->addColumn('form_options', Types::JSON, ['notnull' => false]);

        // Add default_value column for storing Config's default value
        $configTable->addColumn('default_value', Types::TEXT, ['notnull' => false]);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function postUp(Schema $schema): void
    {
        $formOptions = json_encode(['trial_restricted' => true], JSON_THROW_ON_ERROR);

        $this->connection->update(
            'app_config',
            [
                'form_options' => $formOptions,
                'default_value' => '0',
            ],
            [
                'setting_key' => 'system/general/hide_powered_by',
            ]
        );

        $this->connection->update(
            'app_config',
            [
                'form_options' => $formOptions,
                'default_value' => 'no-reply@solidinvoice.co',
            ],
            [
                'setting_key' => 'email/from_address',
            ]
        );

        $this->connection->update(
            'app_config',
            [
                'form_options' => $formOptions,
            ],
            [
                'setting_key' => 'email/sending_options/provider',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $configTable = $schema->getTable('app_config');

        $configTable->dropColumn('form_options');
        $configTable->dropColumn('default_value');
    }
}
