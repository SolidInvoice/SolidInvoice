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

namespace SolidInvoice\InvoiceBundle\Tests\Action;

use Carbon\CarbonImmutable;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Twig\Extension\ModeExtension;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class PdfDemoWatermarkTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string CLIENT_ID = '01JGXKV8QZ0000000000000001';

    private const string INVOICE_ID = '181aaf4a-0097-11ef-9b64-5a2cf21a5680';

    private function makeInvoice(): Invoice
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'name' => 'Johnston PLC'])->_real();
        $client->setId(Ulid::fromString(self::CLIENT_ID));

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => InvoiceStatus::Pending,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'invoiceDate' => CarbonImmutable::parse('2021-09-01'),
                'lines' => [
                    new Line()
                        ->setDescription('Test Line')
                        ->setPrice('100.00')
                        ->setQty(1)
                        ->updateTotal(),
                ],
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();
        $invoice->setId(Ulid::fromString(self::INVOICE_ID))
            ->setInvoiceId('INV-2021-0001');

        return $invoice;
    }

    private function render(bool $demoEnabled): string
    {
        // ModeResolver is a widely-shared private service that is already initialized by the
        // time a test runs, so the container refuses to replace it directly. Overriding the
        // (lazily instantiated) Twig extension that consumes it achieves the same effect.
        self::getContainer()->set(
            ModeExtension::class,
            new ModeExtension($demoEnabled ? new ModeResolver('demo', 'demo@example.com', 'demo-password') : new ModeResolver()),
        );

        return self::getContainer()->get(Environment::class)
            ->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $this->makeInvoice()]);
    }

    public function testDemoWatermarkIsForcedWhenDemoEnabledEvenWithoutWatermarkSetting(): void
    {
        self::assertStringContainsString('content="DEMO"', $this->render(true));
    }

    public function testNoDemoWatermarkWhenDemoDisabled(): void
    {
        self::assertStringNotContainsString('content="DEMO"', $this->render(false));
    }

    public function testDemoWatermarkIsForcedEvenWhenWatermarkSettingDisabled(): void
    {
        self::getContainer()->get(SystemConfig::class)->set('invoice/watermark', '0');

        $rendered = $this->render(true);

        self::assertStringNotContainsString('content="PENDING"', $rendered);
        self::assertStringContainsString('content="DEMO"', $rendered);
    }

    private function renderHtmlView(bool $demoEnabled): string
    {
        self::getContainer()->set(
            ModeExtension::class,
            new ModeExtension($demoEnabled ? new ModeResolver('demo', 'demo@example.com', 'demo-password') : new ModeResolver()),
        );

        return self::getContainer()->get(Environment::class)
            ->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')
            ->renderBlock('content', ['invoice' => $this->makeInvoice(), 'payments' => []]);
    }

    public function testDemoOverlayInInvoiceHtmlView(): void
    {
        self::assertStringContainsString('demo-watermark-overlay', $this->renderHtmlView(true));
    }

    public function testNoDemoOverlayInInvoiceHtmlViewWhenDemoDisabled(): void
    {
        self::assertStringNotContainsString('demo-watermark-overlay', $this->renderHtmlView(false));
    }

    private function renderExternalHtmlView(bool $demoEnabled): string
    {
        self::getContainer()->set(
            ModeExtension::class,
            new ModeExtension($demoEnabled ? new ModeResolver('demo', 'demo@example.com', 'demo-password') : new ModeResolver()),
        );

        return self::getContainer()->get(Environment::class)
            ->render('@SolidInvoiceInvoice/external_invoice_view.html.twig', ['invoice' => $this->makeInvoice()]);
    }

    public function testDemoOverlayInExternalInvoiceHtmlView(): void
    {
        self::assertStringContainsString('demo-watermark-overlay', $this->renderExternalHtmlView(true));
    }

    public function testNoDemoOverlayInExternalInvoiceHtmlViewWhenDemoDisabled(): void
    {
        self::assertStringNotContainsString('demo-watermark-overlay', $this->renderExternalHtmlView(false));
    }

    private function renderRecurringHtmlView(bool $demoEnabled): string
    {
        self::getContainer()->set(
            ModeExtension::class,
            new ModeExtension($demoEnabled ? new ModeResolver('demo', 'demo@example.com', 'demo-password') : new ModeResolver()),
        );

        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'name' => 'Johnston PLC'])->_real();

        $recurringInvoice = RecurringInvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'client' => $client,
                'status' => RecurringInvoiceStatus::Active,
                'total' => '100.00',
                'baseTotal' => '100.00',
                'discount' => new Discount(),
                'tax' => 0,
            ])
            ->_real();
        new ReflectionProperty($recurringInvoice, 'id')->setValue($recurringInvoice, Ulid::fromString(self::INVOICE_ID));

        return self::getContainer()->get(Environment::class)
            ->resolveTemplate('@SolidInvoiceInvoice/Default/view_recurring.html.twig')
            ->renderBlock('content', ['invoice' => $recurringInvoice]);
    }

    public function testDemoOverlayInRecurringInvoiceHtmlView(): void
    {
        self::assertStringContainsString('demo-watermark-overlay', $this->renderRecurringHtmlView(true));
    }

    public function testNoDemoOverlayInRecurringInvoiceHtmlViewWhenDemoDisabled(): void
    {
        self::assertStringNotContainsString('demo-watermark-overlay', $this->renderRecurringHtmlView(false));
    }
}
