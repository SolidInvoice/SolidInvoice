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

namespace SolidInvoice\InvoiceBundle\Tests\Email;

use PHPUnit\Framework\Attributes\CoversNothing;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Notification\InvoiceReminderNotification;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * The reminder templates are shared by the client-facing InvoiceReminderEmail and the staff
 * InvoiceReminderNotification; only the staff copy may carry the notification-preferences footer.
 */
#[CoversNothing]
final class ReminderNotificationFooterTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testOnlyTheStaffCopyHasTheNotificationFooter(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne(['client' => $client, 'company' => $this->company]);

        $twig = self::getContainer()->get(Environment::class);
        $preferencesUrl = self::getContainer()->get(UrlGeneratorInterface::class)->generate('_profile_notifications');
        $clientContext = ['invoice' => $invoice, 'reminder_type' => ReminderType::Overdue1->value, 'days_until_due' => null];

        foreach ([InvoiceReminderNotification::HTML_TEMPLATE, InvoiceReminderNotification::TEXT_TEMPLATE] as $template) {
            self::assertStringNotContainsString($preferencesUrl, (string) $twig->render($template, $clientContext), $template);
            self::assertStringContainsString($preferencesUrl, (string) $twig->render($template, $clientContext + ['notification_email' => true]), $template);
        }
    }
}
