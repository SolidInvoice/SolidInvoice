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

namespace SolidInvoice\InvoiceBundle\Tests\Notification;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Notification\RecurringInvoiceCreatedNotification;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[CoversClass(RecurringInvoiceCreatedNotification::class)]
final class RecurringInvoiceCreatedNotificationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testEmailsShowInvoiceCompanyAndPreferencesLink(): void
    {
        $client = ClientFactory::createOne(['name' => 'Acme Client', 'company' => $this->company]);
        $invoice = InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-RECUR-1', 'company' => $this->company]);

        $twig = self::getContainer()->get(Environment::class);
        $router = self::getContainer()->get(UrlGeneratorInterface::class);
        $parameters = ['invoice' => $invoice];

        foreach ([RecurringInvoiceCreatedNotification::HTML_TEMPLATE, RecurringInvoiceCreatedNotification::TEXT_TEMPLATE] as $template) {
            $output = $twig->render($template, $parameters);

            self::assertStringContainsString('INV-RECUR-1', (string) $output, $template);
            self::assertStringContainsString('Acme Client', (string) $output, $template);
            self::assertStringContainsString($router->generate('_invoices_view', ['id' => $invoice->getId()]), (string) $output, $template);
            self::assertStringContainsString($router->generate('_profile_notifications'), (string) $output, $template);
            self::assertStringContainsString('You are receiving this notification for', (string) $output, $template);
        }
    }

    public function testSubjectIncludesInvoiceNumber(): void
    {
        $invoice = InvoiceFactory::createOne(['invoiceId' => 'INV-RECUR-2', 'company' => $this->company]);
        $notification = new RecurringInvoiceCreatedNotification(['invoice' => $invoice]);

        $subject = self::getContainer()->get(TranslatorInterface::class)
            ->trans($notification->getSubjectKey(), $notification->getSubjectParameters(), 'email');

        self::assertSame('Invoice INV-RECUR-2 was created from a recurring invoice', $subject);
    }
}
