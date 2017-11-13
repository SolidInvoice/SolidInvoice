<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Application\Migrations;

use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\NotificationBundle\Entity\Notification;
use SolidInvoice\NotificationBundle\Form\Type\HipChatColorType;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;
use SolidInvoice\SettingsBundle\Form\Type\AddressType;
use SolidInvoice\SettingsBundle\Form\Type\MailEncryptionType;
use SolidInvoice\SettingsBundle\Form\Type\MailFormatType;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\SettingsBundle\Form\Type\ThemeType;
use SolidInvoice\TaxBundle\Form\Type\TaxNumberType;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
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
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE app_config DROP FOREIGN KEY FK_318942FCD823E37A');
        $this->addSql('ALTER TABLE config_sections DROP FOREIGN KEY FK_965EAD46727ACA70');
        $this->addSql('DROP TABLE config_sections');
        $this->addSql('ALTER TABLE notifications CHANGE email email TINYINT(1) NOT NULL, CHANGE hipchat hipchat TINYINT(1) NOT NULL, CHANGE sms sms TINYINT(1) NOT NULL');
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

        $this->addSql('ALTER TABLE clients ADD vat_number VARCHAR(255) DEFAULT NULL');

        $this->addSql('UPDATE invoices set discount = discount * 100');
        $this->addSql('UPDATE quotes set discount = discount * 100');
        $this->addSql('ALTER TABLE invoices CHANGE discount discount_value_percentage DOUBLE PRECISION DEFAULT NULL, ADD discount_valueMoney_amount INT NOT NULL, ADD discount_valueMoney_currency VARCHAR(3) DEFAULT NULL, ADD discount_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes CHANGE discount discount_value_percentage DOUBLE PRECISION DEFAULT NULL, ADD discount_valueMoney_amount INT NOT NULL, ADD discount_valueMoney_currency VARCHAR(3) DEFAULT NULL, ADD discount_type VARCHAR(255) DEFAULT NULL');

        $this->addSql('UPDATE invoices set discount_type = "'.Discount::TYPE_PERCENTAGE.'"');
        $this->addSql('UPDATE quotes set discount_type = "'.Discount::TYPE_PERCENTAGE.'"');

        $this->addSql('CREATE TABLE invoice_contact (invoice_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_BEBBD0EB2989F1FD (invoice_id), INDEX IDX_BEBBD0EBE7A1254A (contact_id), PRIMARY KEY(invoice_id, contact_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quote_contact (quote_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_A38D4EBCDB805178 (quote_id), INDEX IDX_A38D4EBCE7A1254A (contact_id), PRIMARY KEY(quote_id, contact_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice_contact ADD CONSTRAINT FK_BEBBD0EB2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)');
        $this->addSql('ALTER TABLE invoice_contact ADD CONSTRAINT FK_BEBBD0EBE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id)');
        $this->addSql('ALTER TABLE quote_contact ADD CONSTRAINT FK_A38D4EBCDB805178 FOREIGN KEY (quote_id) REFERENCES quotes (id)');
        $this->addSql('ALTER TABLE quote_contact ADD CONSTRAINT FK_A38D4EBCE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id)');

        $this->addSql('ALTER TABLE invoices DROP users');
        $this->addSql('ALTER TABLE quotes DROP users');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // TODO: Should we try to restore the settings to it's original state?

        $this->addSql('DROP INDEX UNIQ_1483A5E9C05FB297 ON users');
        $this->addSql('ALTER TABLE users ADD locked TINYINT(1) NOT NULL, ADD expired TINYINT(1) NOT NULL, ADD expires_at DATETIME DEFAULT NULL, ADD credentials_expired TINYINT(1) NOT NULL, ADD credentials_expire_at DATETIME DEFAULT NULL, CHANGE username username VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE username_canonical username_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE email_canonical email_canonical VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE salt salt VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE confirmation_token confirmation_token VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');

        $this->addSql('UPDATE tax_rates SET rate = rate / 100');
        $this->addSql('UPDATE tax_rates SET tax_type = "inclusive" WHERE tax_type = "Inclusive"');
        $this->addSql('UPDATE tax_rates SET tax_type = "exlusive" WHERE tax_type = "Exlusive"');

        $this->addSql('ALTER TABLE notifications CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE hipchat hipchat VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE sms sms VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');

        $this->addSql('ALTER TABLE clients DROP vat_number');

        $this->addSql('ALTER TABLE client_credit CHANGE value_amount value_amount INT DEFAULT NULL');
        $this->addSql('ALTER TABLE 
          invoices CHANGE total_amount total_amount INT DEFAULT NULL, 
          CHANGE baseTotal_amount baseTotal_amount INT DEFAULT NULL, 
          CHANGE tax_amount tax_amount INT DEFAULT NULL, 
          CHANGE balance_amount balance_amount INT DEFAULT NULL, 
          CHANGE discount_valueMoney_amount discount_valueMoney_amount INT DEFAULT NULL');
        $this->addSql('ALTER TABLE 
          invoice_lines CHANGE price_amount price_amount INT DEFAULT NULL, 
          CHANGE total_amount total_amount INT DEFAULT NULL');
        $this->addSql('ALTER TABLE 
          quotes CHANGE total_amount total_amount INT DEFAULT NULL, 
          CHANGE baseTotal_amount baseTotal_amount INT DEFAULT NULL, 
          CHANGE tax_amount tax_amount INT DEFAULT NULL, 
          CHANGE discount_valueMoney_amount discount_valueMoney_amount INT DEFAULT NULL');
        $this->addSql('ALTER TABLE 
          quote_lines CHANGE price_amount price_amount INT DEFAULT NULL, 
          CHANGE total_amount total_amount INT DEFAULT NULL');

        $this->addSql('DROP TABLE invoice_contact');
        $this->addSql('DROP TABLE quote_contact');
        $this->addSql('ALTER TABLE invoices ADD users LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE quotes ADD users LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\'');
    }

    private function updateSettings(): void
    {
        $sections = $this->connection->fetchAll('SELECT * FROM config_sections');

        foreach ($sections as $section) {
            $settings = [];

            if ('general' === $section['name']) {
                $section['name'] = 'company';
            }

            $path = $section['name'];

            $settingsBySection = $this->connection->fetchAll('SELECT * FROM app_config WHERE section_id = :section', [':section' => $section['id']]);

            while (null !== ($section['parent_id'] ?? null ?: null)) {
                $section = $this->connection->fetchAssoc('SELECT * FROM config_sections WHERE id = :section', ['section' => $section['parent_id']]);

                if ('general' === $section['name']) {
                    $section['name'] = 'company';
                }

                $path = $section['name'].'/'.$path;
            }

            foreach ($settingsBySection as $v) {
                if ('app_name' === $v['setting_key']) {
                    $v['setting_key'] = 'company_name';
                }

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

        $additionalSettings = [
            'design/system/theme' => [
                'setting_value' => 'skin-blue',
                'field_type' => ThemeType::class,
            ],
            'system/company/vat_number' => [
                'field_type' => TaxNumberType::class,
            ],
            'system/company/contact_details/email' => [
                'field_type' => EmailType::class,
            ],
            'system/company/contact_details/phone_number' => [
                'field_type' => TextType::class,
            ],
            'system/company/contact_details/address' => [
                'field_type' => AddressType::class,
            ],
        ];

        foreach ($additionalSettings as $key => $settingConfig) {
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
                    $key,
                    !empty($settingConfig['setting_value']) ? '"'.$settingConfig['setting_value'].'"' : 'NULL',
                    !empty($settingConfig['description']) ? '"'.$settingConfig['description'].'"' : 'NULL',
                    addslashes($settingConfig['field_type'])
                )
            );
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
            'mailer_host' => [
                'path' => 'email/sending_options/host',
                'type' => addslashes(TextType::class),
            ],
            'mailer_user' => [
                'path' => 'email/sending_options/user',
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
            'mailer_encryption' => [
                'path' => 'email/sending_options/encryption',
                'type' => addslashes(MailEncryptionType::class),
            ],
            'currency' => [
                'path' => 'system/company/currency',
                'type' => addslashes(CurrencyType::class),
            ],
        ];

        foreach ($keys as $key => $value) {
            try {
                $parameter = $this->container->getParameter($key);
            } catch (InvalidArgumentException $e) {
                try {
                    $parameter = $this->container->getParameter(sprintf('env(%s)', $key));
                } catch (InvalidArgumentException $e) {
                    continue;
                }
            }

            if ('mailer_transport' === $key && 'mail' === $parameter) {
                $parameter = 'sendmail';
            }

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
