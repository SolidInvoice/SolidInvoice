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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Templates;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\InvoiceBundle\Twig\Extension\InvoiceTemplateExtension
 */
final class TemplatesRenderingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const SLUGS = [
        'classic',
        'modern',
        'compact',
        'editorial',
        'monochrome',
        'photographer',
        'studio',
        'friendly',
    ];

    private const CHANNELS = ['pdf', 'email', 'preview'];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function templateProvider(): iterable
    {
        foreach (self::SLUGS as $slug) {
            foreach (self::CHANNELS as $channel) {
                yield "{$slug}/{$channel}" => [$slug, $channel];
            }
        }
    }

    #[DataProvider('templateProvider')]
    public function testTemplateRenders(string $slug, string $channel): void
    {
        $invoice = $this->createFixtureInvoice();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/%s.html.twig', $slug, $channel),
            ['invoice' => $invoice]
        );

        self::assertNotEmpty($output, "Template {$slug}/{$channel} produced empty output");
        self::assertStringContainsString($invoice->getInvoiceId(), $output);
        self::assertStringContainsString((string) $invoice->getClient(), $output);

        match ($channel) {
            'pdf' => self::assertStringContainsString('</html>', $output),
            'email' => self::assertStringContainsString('schema.org', $output),
            'preview' => self::assertStringContainsString('Sample line item', $output),
            default => self::fail("Unknown channel: {$channel}"),
        };
    }

    #[DataProvider('pdfTemplateProvider')]
    public function testPdfTemplateGenerates(string $slug): void
    {
        $generator = self::getContainer()->get(Generator::class);
        self::assertInstanceOf(Generator::class, $generator);

        if (! $generator->canPrintPdf()) {
            self::markTestSkipped('PDF generation requires mbstring + gd extensions.');
        }

        $invoice = $this->createFixtureInvoice();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/pdf.html.twig', $slug),
            ['invoice' => $invoice]
        );

        $pdf = $generator->generate($html);
        self::assertNotEmpty($pdf);
        self::assertStringStartsWith('%PDF-', $pdf);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pdfTemplateProvider(): iterable
    {
        foreach (self::SLUGS as $slug) {
            yield $slug => [$slug];
        }
    }

    private function createFixtureInvoice(): Invoice
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'name' => 'Acme Corp',
            'currencyCode' => 'USD',
        ]);

        $contact = ContactFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'invoiceId' => 'INV-FIXTURE-001',
            'due' => new DateTimeImmutable('+14 days'),
            'paidDate' => null,
            'archived' => null,
            'terms' => 'Payment due within 30 days.',
            'notes' => 'Thank you for your business.',
            'balance' => BigInteger::of(150000),
            'total' => BigInteger::of(150000),
            'baseTotal' => BigInteger::of(150000),
            'tax' => BigInteger::of(0),
            'users' => [$contact],
        ]);

        $real = $invoice->_real();

        $line = (new Line())
            ->setDescription('Sample line item')
            ->setPrice(BigInteger::of(75000))
            ->setQty(2.0)
            ->setTotal(BigInteger::of(150000));
        $real->addLine($line);

        $invoice->_save();

        return $real;
    }
}
