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

use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class ManualInvoiceReminderEmailTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use MatchesSnapshots;

    private const INVOICE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    private Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twig = self::getContainer()->get('twig');
    }

    public function testEmailHasCorrectSubject(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $invoice->_real()->setInvoiceId('INV-2024-001');

        $email = new ManualInvoiceReminderEmail($invoice->_real());

        self::assertSame('Payment Reminder: Invoice INV-2024-001', $email->getSubject());
    }

    public function testEmailHasCorrectTemplates(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $email = new ManualInvoiceReminderEmail($invoice->_real());

        self::assertSame('@SolidInvoiceInvoice/Email/manual_reminder.html.twig', $email->getHtmlTemplate());
        self::assertSame('@SolidInvoiceInvoice/Email/manual_reminder.text.twig', $email->getTextTemplate());
    }

    public function testEmailContextIncludesInvoice(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $email = new ManualInvoiceReminderEmail($invoice->_real());

        $context = $email->getContext();
        self::assertArrayHasKey('invoice', $context);
        self::assertSame($invoice->_real(), $context['invoice']);
    }

    public function testGetInvoiceReturnsCorrectInvoice(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $email = new ManualInvoiceReminderEmail($invoice->_real());

        self::assertSame($invoice->_real(), $email->getInvoice());
    }

    public function testHtmlTemplateRendersCorrectly(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Acme Corporation',
        ]);

        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 25000,
                'balance' => 25000,
                'baseTotal' => 25000,
                'created' => new DateTimeImmutable('2024-01-15'),
                'invoiceDate' => new DateTimeImmutable('2024-01-15'),
                'due' => new DateTimeImmutable('2024-02-15'),
                'lines' => [
                    (new Line())
                        ->setDescription('Monthly Subscription')
                        ->setPrice(25000)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2024-001');

        $email = new ManualInvoiceReminderEmail($invoice);

        $rendered = $this->twig->render($email->getHtmlTemplate(), $email->getContext());

        $this->assertMatchesHtmlSnapshot($rendered);
    }

    public function testTextTemplateRendersCorrectly(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Acme Corporation',
        ]);

        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => 25000,
                'balance' => 25000,
                'baseTotal' => 25000,
                'created' => new DateTimeImmutable('2024-01-15'),
                'invoiceDate' => new DateTimeImmutable('2024-01-15'),
                'due' => new DateTimeImmutable('2024-02-15'),
                'lines' => [
                    (new Line())
                        ->setDescription('Monthly Subscription')
                        ->setPrice(25000)
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'tax' => 0,
            ])
            ->_real();

        $uuid = Ulid::fromString(self::INVOICE_ID);
        $invoice->setId($uuid)
            ->setUuid(Uuid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2024-001');

        $email = new ManualInvoiceReminderEmail($invoice);

        $rendered = $this->twig->render($email->getTextTemplate(), $email->getContext());

        $this->assertMatchesTextSnapshot($rendered);
    }
}
