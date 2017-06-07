<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use CSBill\CoreBundle\Form\Type\ImageUploadType;
use CSBill\CoreBundle\Form\Type\Select2Type;
use CSBill\MoneyBundle\Form\Type\CurrencyType;
use CSBill\NotificationBundle\Entity\Notification;
use CSBill\NotificationBundle\Form\Type\HipChatColorType;
use CSBill\NotificationBundle\Form\Type\NotificationType;
use CSBill\SettingsBundle\Form\Type\MailEncryptionType;
use CSBill\SettingsBundle\Form\Type\MailFormatType;
use CSBill\SettingsBundle\Form\Type\MailTransportType;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class Version200 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE app_config DROP FOREIGN KEY FK_318942FCD823E37A');
        $this->addSql('ALTER TABLE config_sections DROP FOREIGN KEY FK_965EAD46727ACA70');
        $this->addSql('DROP TABLE config_sections');
        $this->addSql('ALTER TABLE invoice_lines RENAME INDEX idx_dcc4b9f82989f1fd TO IDX_72DBDC232989F1FD');
        $this->addSql('ALTER TABLE invoice_lines RENAME INDEX idx_dcc4b9f8b2a824d8 TO IDX_72DBDC23B2A824D8');
        $this->addSql('ALTER TABLE notifications CHANGE email email TINYINT(1) NOT NULL, CHANGE hipchat hipchat TINYINT(1) NOT NULL, CHANGE sms sms TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE quote_lines RENAME INDEX idx_ece1642cdb805178 TO IDX_42FE01F7DB805178');
        $this->addSql('ALTER TABLE quote_lines RENAME INDEX idx_ece1642cb2a824d8 TO IDX_42FE01F7B2A824D8');
        $this->addSql('DROP INDEX IDX_318942FCD823E37A ON app_config');
        $this->addSql('ALTER TABLE app_config DROP section_id, DROP field_options');
        $this->addSql('TRUNCATE TABLE app_config');
        $this->addSql('ALTER TABLE app_config CHANGE field_type field_type VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_318942FC5FA1E697 ON app_config (setting_key)');

        $this->updateSettings();

        $this->addSql('ALTER TABLE users DROP locked, DROP expired, DROP expires_at, DROP credentials_expired, DROP credentials_expire_at, CHANGE username username VARCHAR(180) NOT NULL, CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL, CHANGE email_canonical email_canonical VARCHAR(180) NOT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9C05FB297 ON users (confirmation_token)');

        $this->addSql('UPDATE tax_rates SET rate = rate * 100');
        $this->addSql('UPDATE tax_rates SET tax_type = "Inclusive" WHERE tax_type = "inclusive"');
        $this->addSql('UPDATE tax_rates SET tax_type = "Exlusive" WHERE tax_type = "exlusive"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // TODO: Should we try to restore the settings to it's original state?

        $this->addSql('DROP INDEX UNIQ_1483A5E9C05FB297 ON users');
        $this->addSql('ALTER TABLE users ADD locked TINYINT(1) NOT NULL, ADD expired TINYINT(1) NOT NULL, ADD expires_at DATETIME DEFAULT NULL, ADD credentials_expired TINYINT(1) NOT NULL, ADD credentials_expire_at DATETIME DEFAULT NULL, CHANGE username username VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE username_canonical username_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email_canonical email_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE salt salt VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');

        $this->addSql('UPDATE tax_rates SET rate = rate / 100');
        $this->addSql('UPDATE tax_rates SET tax_type = "inclusive" WHERE tax_type = "Inclusive"');
        $this->addSql('UPDATE tax_rates SET tax_type = "exlusive" WHERE tax_type = "Exlusive"');

        $this->addSql('ALTER TABLE invoice_lines RENAME INDEX idx_72dbdc232989f1fd TO IDX_DCC4B9F82989F1FD');
        $this->addSql('ALTER TABLE invoice_lines RENAME INDEX idx_72dbdc23b2a824d8 TO IDX_DCC4B9F8B2A824D8');
        $this->addSql('ALTER TABLE notifications CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE hipchat hipchat VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE sms sms VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE quote_lines RENAME INDEX idx_42fe01f7db805178 TO IDX_ECE1642CDB805178');
        $this->addSql('ALTER TABLE quote_lines RENAME INDEX idx_42fe01f7b2a824d8 TO IDX_ECE1642CB2A824D8');
    }

    private function updateSettings(): void
    {
        $sections = $this->connection->fetchAll('SELECT * FROM config_sections');

        foreach ($sections as $section) {
            $settings = [];

            $path = $section['name'];

            $settingsBySection = $this->connection->fetchAll('SELECT * FROM app_config WHERE section_id = :section', [':section' => $section['id']]);

            while (null !== ($section['parent_id'] ?? null ?: null)) {
                $section = $this->connection->fetchAssoc('SELECT * FROM config_sections WHERE id = :section', ['section' => $section['parent_id']]);
                $path = $section['name'].'/'.$path;
            }

            foreach ($settingsBySection as $v) {
                $settings[$path.'/'.$v['setting_key']] = $v;
            }

            foreach ($settings as $settingKey => $settingValue) {
                $this->addSql(
                    sprintf(
                        'INSERT INTO app_config (
                            setting_key,
                            setting_value,
                            description,
                            field_type
                        ) VALUES (
                          "%s",
                          %s,
                          %s,
                          "%s"
                        )',
                        $settingKey,
                        !empty($settingValue['setting_value']) ? '"'.$settingValue['setting_value'].'"' : 'NULL',
                        !empty($settingValue['description']) ? '"'.$settingValue['description'].'"' : 'NULL',
                        (function ($settingKey, $settingValue) {
                            if ('email/format' === $settingKey) {
                                return addslashes(MailFormatType::class);
                            }

                            if ('hipchat/message_color' === $settingKey) {
                                return addslashes(HipChatColorType::class);
                            }

                            switch ($settingValue['field_type']) {
                                case 'select2':
                                    return addslashes(Select2Type::class);
                                    break;
                                case 'radio':
                                    return addslashes(RadioType::class);
                                    break;
                                case 'checkbox':
                                    return addslashes(CheckboxType::class);
                                    break;
                                case 'email':
                                    return addslashes(EmailType::class);
                                    break;
                                case 'image_upload':
                                    return addslashes(ImageUploadType::class);
                                    break;
                                case 'text':
                                case null:
                                default:
                                    return addslashes(TextType::class);
                                    break;
                            }
                        })($settingKey, $settingValue)
                    )
                );
            }
        }

        /** @var Notification $notification */
        foreach ($this->connection->fetchAll('SELECT * FROM notifications') as $notification) {
            $this->addSql(
                sprintf(
                    'INSERT INTO app_config (
                            setting_key,
                            setting_value,
                            field_type
                        ) VALUES (
                          "%s",
                          "%s",
                          "%s"
                        )',
                    'notification/'.$notification['notification_event'],
                    addslashes(json_encode(['email' => (bool) $notification['email'], 'hipchat' => (bool) $notification['hipchat'], 'sms' => (bool) $notification['sms']])),
                    addslashes(NotificationType::class)
                )
            );
        }

        $keys = [
            'mailer_transport' => [
                'path' => 'email/sending_options/transport',
                'type' => addslashes(MailTransportType::class),
            ],
            'mailer_encryption' => [
                'path' => 'email/sending_options/encryption',
                'type' => addslashes(MailEncryptionType::class),
            ],
            'mailer_host' => [
                'path' => 'email/sending_options/host',
                'type' => addslashes(TextType::class),
            ],
            'mailer_password' => [
                'path' => 'email/sending_options/password',
                'type' => addslashes(PasswordType::class),
            ],
            'mailer_port' => [
                'path' => 'email/sending_options/port',
                'type' => addslashes(TextType::class),
            ],
            'mailer_user' => [
                'path' => 'email/sending_options/user',
                'type' => addslashes(TextType::class),
            ],
            'currency' => [
                'path' => 'system/general/currency',
                'type' => addslashes(CurrencyType::class),
            ],
        ];

        foreach ($keys as $key => $value) {
            $parameter = $this->container->getParameter($key);

            $this->addSql(
                sprintf(
                    'INSERT INTO app_config (
                            setting_key,
                            setting_value,
                            field_type
                        ) VALUES (
                          "%s",
                          %s,
                          "%s"
                        )',
                    $value['path'],
                    !empty($parameter) ? '"'.$parameter.'"' : 'NULL',
                    $value['type']
                )
            );
        }
    }
}
