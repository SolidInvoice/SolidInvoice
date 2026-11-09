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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Form\Type\CompanyNameSettingType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class Version30200_2 extends AbstractMigration
{
    private const string SETTING_KEY = 'system/company/company_name';

    public function getDescription(): string
    {
        return 'Switch the company name setting to CompanyNameSettingType, so the settings page validates it';
    }

    public function up(Schema $schema): void
    {
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $this->connection->update('app_config', ['field_type' => CompanyNameSettingType::class], ['setting_key' => self::SETTING_KEY]);
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->update('app_config', ['field_type' => TextType::class], ['setting_key' => self::SETTING_KEY]);
    }
}
