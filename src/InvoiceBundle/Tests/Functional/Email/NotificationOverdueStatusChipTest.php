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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Email;

use DateTimeImmutable;
use DateTimeZone;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Notification\InvoiceOverdueNotification;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;

/**
 * `a11y` F1: the overdue notice is the one place a customer reads an
 * invoice status, so its chip must render as a chip, not as unstyled
 * text. A class name alone does not prove that; the CSS inliner
 * (`inline_css()` in `Layout/Email/base.html.twig`) must actually match
 * `.status-chip`/`.status-chip--danger` in `modern.css.twig` and copy
 * their declarations onto the span as a `style` attribute.
 */
final class NotificationOverdueStatusChipTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private const string INVOICE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    public function testOverdueStatusChipIsInlinedWithDangerColours(): void
    {
        $invoice = $this->createFixtureOverdueInvoice();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $rendered = $twig->render(InvoiceOverdueNotification::HTML_TEMPLATE, [
            'invoice' => $invoice,
            'client' => $invoice->getClient(),
        ]);

        self::assertMatchesRegularExpression(
            '/<span class="status-chip status-chip--danger" style="[^"]*background-color:\s*#fee2e2;[^"]*color:\s*#b91c1c;/',
            $rendered,
        );
    }

    private function createFixtureOverdueInvoice(): Invoice
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Acme Corporation',
        ]);

        $contact = ContactFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'status' => InvoiceStatus::Overdue,
                'total' => 150000,
                'balance' => 150000,
                'baseTotal' => 150000,
                'tax' => 0,
                'created' => new DateTimeImmutable('2024-01-15', new DateTimeZone('UTC')),
                'invoiceDate' => new DateTimeImmutable('2024-01-15', new DateTimeZone('UTC')),
                'due' => new DateTimeImmutable('2024-02-15', new DateTimeZone('UTC')),
                'discount' => new Discount()
                    ->setType(null),
                'lines' => [
                    new Line()
                        ->setDescription('Sample line item')
                        ->setPrice(75000)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'users' => [$contact],
            ]);

        $invoice
            ->setId(Ulid::fromString(self::INVOICE_ID))
            ->setUuid(Uuid::fromString(self::INVOICE_ID));

        return $invoice;
    }
}
