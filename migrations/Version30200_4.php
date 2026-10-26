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

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version30200_4 extends AbstractMigration
{
    private const string TABLE_NAME = 'app_config';

    /**
     * Setting key => [description stored by older releases, translation key the ConfigProviders now store].
     *
     * @var array<string, array{string, string}>
     */
    private const array DESCRIPTIONS = [
        'invoice/watermark' => ['Display a watermark on the invoice with the status', 'settings.description.invoice.watermark'],
        'invoice/bcc_address' => ['Send BCC copy of invoice to this address', 'settings.description.invoice.bcc_address'],
        'invoice/email_subject' => ['To include the id of the invoice in the subject, add the placeholder {id} where you want the id', 'settings.description.invoice.email_subject'],
        'invoice/payment_terms' => ['Default payment terms for new invoices. A client can override this.', 'settings.description.invoice.payment_terms'],
        'invoice/id_generation/id_prefix' => ['Example: INV-', 'settings.description.invoice.id_generation.id_prefix'],
        'invoice/id_generation/id_suffix' => ['Example: -INV', 'settings.description.invoice.id_generation.id_suffix'],
        'invoice/reminder/enabled' => ['Enable automatic invoice payment reminders', 'settings.description.invoice.reminder.enabled'],
        'invoice/reminder/pre_due_enabled' => ['Send reminder before invoice is due', 'settings.description.invoice.reminder.pre_due_enabled'],
        'invoice/reminder/pre_due_days' => ['Days before due date to send pre-due reminder (0 to disable)', 'settings.description.invoice.reminder.pre_due_days'],
        'quote/watermark' => ['Display a watermark on the quote with the status', 'settings.description.quote.watermark'],
        'quote/bcc_address' => ['Send BCC copy of quote to this address', 'settings.description.quote.bcc_address'],
        'quote/email_subject' => ['To include the id of the quote in the subject, add the placeholder {id} where you want the id', 'settings.description.quote.email_subject'],
        'quote/id_generation/id_prefix' => ['Example: QUOT-', 'settings.description.quote.id_generation.id_prefix'],
        'quote/id_generation/id_suffix' => ['Example: -QUOT', 'settings.description.quote.id_generation.id_suffix'],
        'system/general/hide_powered_by' => ['Hide "Powered by SolidInvoice" text in invoices and quotes.', 'settings.description.system.general.hide_powered_by'],
        'system/domain/custom_domain' => ['Custom domain for this company (leave empty to use the default URL).', 'settings.description.system.domain.custom_domain'],
        'design/template' => ['Design template used for invoices and quotes everywhere clients see them: PDF downloads, emails and the client portal.', 'settings.description.design.template'],
    ];

    public function getDescription(): string
    {
        return 'Replace the English setting descriptions stored per company with translation keys';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        foreach (self::DESCRIPTIONS as $key => [$english, $translationKey]) {
            $this->addSql(
                'UPDATE ' . self::TABLE_NAME . ' SET description = ? WHERE setting_key = ? AND description = ?',
                [$translationKey, $key, $english],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::DESCRIPTIONS as $key => [$english, $translationKey]) {
            $this->addSql(
                'UPDATE ' . self::TABLE_NAME . ' SET description = ? WHERE setting_key = ? AND description = ?',
                [$english, $key, $translationKey],
            );
        }
    }
}
