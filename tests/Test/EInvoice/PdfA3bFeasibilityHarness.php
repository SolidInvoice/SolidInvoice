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

namespace SolidInvoice\Test\EInvoice;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use function array_unique;
use function array_values;
use function basename;
use function dirname;
use function file_put_contents;
use function fwrite;
use function glob;
use function is_dir;
use function mkdir;
use function sort;
use function sprintf;
use function str_starts_with;

/**
 * SOL-143 feasibility harness. Not a production test.
 *
 * It renders the real invoice PDF templates, drives mpdf with the PDF/A-3B and
 * Factur-X settings from the SOL-24 spike, and writes each PDF to the directory
 * in `SOL143_OUT`. veraPDF then validates the files. The harness proves nothing
 * on its own; the veraPDF verdict is the result.
 *
 * Run it with:
 *
 *     SOL143_OUT=/some/dir bin/phpunit tests/Test/EInvoice/PdfA3bFeasibilityHarness.php
 */
final class PdfA3bFeasibilityHarness extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * The Factur-X XMP extension schema. PDF/A requires a description for every
     * XMP property outside the predefined schemas, so a real Factur-X producer
     * emits this block next to the `fx:` properties.
     */
    private const string XMP_RDF = <<<'XML'
        <rdf:Description rdf:about="" xmlns:pdfaExtension="http://www.aiim.org/pdfa/ns/extension/" xmlns:pdfaSchema="http://www.aiim.org/pdfa/ns/schema#" xmlns:pdfaProperty="http://www.aiim.org/pdfa/ns/property#">
          <pdfaExtension:schemas>
            <rdf:Bag>
              <rdf:li rdf:parseType="Resource">
                <pdfaSchema:schema>Factur-X PDFA Extension Schema</pdfaSchema:schema>
                <pdfaSchema:namespaceURI>urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#</pdfaSchema:namespaceURI>
                <pdfaSchema:prefix>fx</pdfaSchema:prefix>
                <pdfaSchema:property>
                  <rdf:Seq>
                    <rdf:li rdf:parseType="Resource">
                      <pdfaProperty:name>DocumentFileName</pdfaProperty:name>
                      <pdfaProperty:valueType>Text</pdfaProperty:valueType>
                      <pdfaProperty:category>external</pdfaProperty:category>
                      <pdfaProperty:description>Name of the embedded XML invoice file</pdfaProperty:description>
                    </rdf:li>
                    <rdf:li rdf:parseType="Resource">
                      <pdfaProperty:name>DocumentType</pdfaProperty:name>
                      <pdfaProperty:valueType>Text</pdfaProperty:valueType>
                      <pdfaProperty:category>external</pdfaProperty:category>
                      <pdfaProperty:description>INVOICE</pdfaProperty:description>
                    </rdf:li>
                    <rdf:li rdf:parseType="Resource">
                      <pdfaProperty:name>Version</pdfaProperty:name>
                      <pdfaProperty:valueType>Text</pdfaProperty:valueType>
                      <pdfaProperty:category>external</pdfaProperty:category>
                      <pdfaProperty:description>The actual version of the Factur-X XML schema</pdfaProperty:description>
                    </rdf:li>
                    <rdf:li rdf:parseType="Resource">
                      <pdfaProperty:name>ConformanceLevel</pdfaProperty:name>
                      <pdfaProperty:valueType>Text</pdfaProperty:valueType>
                      <pdfaProperty:category>external</pdfaProperty:category>
                      <pdfaProperty:description>The conformance level of the embedded Factur-X data</pdfaProperty:description>
                    </rdf:li>
                  </rdf:Seq>
                </pdfaSchema:property>
              </rdf:li>
            </rdf:Bag>
          </pdfaExtension:schemas>
        </rdf:Description>
        <rdf:Description rdf:about="" xmlns:fx="urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#">
          <fx:DocumentType>INVOICE</fx:DocumentType>
          <fx:DocumentFileName>factur-x.xml</fx:DocumentFileName>
          <fx:Version>1.0</fx:Version>
          <fx:ConformanceLevel>EN 16931</fx:ConformanceLevel>
        </rdf:Description>
        XML;

    /**
     * A Factur-X MINIMUM profile CII document. Its content does not affect the
     * PDF/A verdict; it only has to be a real attachment of a real size.
     */
    private const string FACTUR_X_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">
          <rsm:ExchangedDocumentContext>
            <ram:GuidelineSpecifiedDocumentContextParameter>
              <ram:ID>urn:cen.eu:en16931:2017</ram:ID>
            </ram:GuidelineSpecifiedDocumentContextParameter>
          </rsm:ExchangedDocumentContext>
          <rsm:ExchangedDocument>
            <ram:ID>INV-FIXTURE-001</ram:ID>
            <ram:TypeCode>380</ram:TypeCode>
            <ram:IssueDateTime><udt:DateTimeString format="102">20260922</udt:DateTimeString></ram:IssueDateTime>
          </rsm:ExchangedDocument>
          <rsm:SupplyChainTradeTransaction>
            <ram:ApplicableHeaderTradeAgreement>
              <ram:SellerTradeParty><ram:Name>SolidInvoice</ram:Name></ram:SellerTradeParty>
              <ram:BuyerTradeParty><ram:Name>Acme Corp</ram:Name></ram:BuyerTradeParty>
            </ram:ApplicableHeaderTradeAgreement>
            <ram:ApplicableHeaderTradeDelivery/>
            <ram:ApplicableHeaderTradeSettlement>
              <ram:InvoiceCurrencyCode>USD</ram:InvoiceCurrencyCode>
              <ram:SpecifiedTradeSettlementHeaderMonetarySummation>
                <ram:TaxBasisTotalAmount>1500.00</ram:TaxBasisTotalAmount>
                <ram:TaxTotalAmount currencyID="USD">0.00</ram:TaxTotalAmount>
                <ram:GrandTotalAmount>1500.00</ram:GrandTotalAmount>
                <ram:DuePayableAmount>1500.00</ram:DuePayableAmount>
              </ram:SpecifiedTradeSettlementHeaderMonetarySummation>
            </ram:ApplicableHeaderTradeSettlement>
          </rsm:SupplyChainTradeTransaction>
        </rsm:CrossIndustryInvoice>
        XML;

    /**
     * @return iterable<string, array{string}>
     */
    public static function templateProvider(): iterable
    {
        // The standalone document the mailer attaches.
        yield 'standalone' => ['@SolidInvoiceInvoice/Pdf/invoice.html.twig'];

        $directories = glob(dirname(__DIR__, 3) . '/src/InvoiceBundle/Resources/views/Templates/*', GLOB_ONLYDIR) ?: [];
        $slugs = [];

        foreach ($directories as $directory) {
            $slug = basename($directory);

            if (! str_starts_with($slug, '_')) {
                $slugs[] = $slug;
            }
        }

        sort($slugs);

        foreach ($slugs as $slug) {
            yield $slug => [sprintf('@SolidInvoiceInvoice/Templates/%s/pdf.html.twig', $slug)];
        }
    }

    #[DataProvider('templateProvider')]
    public function testArchivalPdfIsWritten(string $template): void
    {
        $outputDirectory = $_SERVER['SOL143_OUT'] ?? null;
        self::assertIsString($outputDirectory, 'Set SOL143_OUT to the directory the PDFs are written to');

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0o775, true);
        }

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->render($template, ['invoice' => $this->createFixtureInvoice()]);

        // The real stylesheet has to reach the PDF. Without it the run proves nothing.
        self::assertStringContainsString('.invoice-header-table', $html, 'static/pdf.css did not reach the rendered HTML');
        self::assertStringNotContainsString('opacity: 0.9', $html, 'The opacity rule is still in static/pdf.css');

        $name = self::nameFor($template);

        // Keep the HTML so the control runs can drive mpdf directly, without the kernel.
        file_put_contents(sprintf('%s/%s.html', $outputDirectory, $name), $html);

        // `PDFAauto => false` makes mpdf throw rather than fix. Run it first, because
        // the warnings it raises name every thing mpdf knows is not conformant.
        $strict = $this->generateArchival($html, false);

        foreach ($strict['warnings'] as $warning) {
            fwrite(STDERR, sprintf("[%s][auto=false] %s\n", $name, $warning));
        }

        if (null !== $strict['pdf']) {
            file_put_contents(sprintf('%s/%s-strict.pdf', $outputDirectory, $name), $strict['pdf']);
        }

        // Then with the auto-fix on, so there is a file for veraPDF either way.
        $auto = $this->generateArchival($html, true);

        foreach ($auto['warnings'] as $warning) {
            fwrite(STDERR, sprintf("[%s][auto=true] %s\n", $name, $warning));
        }

        self::assertIsString($auto['pdf']);
        self::assertStringStartsWith('%PDF-', $auto['pdf']);

        file_put_contents(sprintf('%s/%s-auto.pdf', $outputDirectory, $name), $auto['pdf']);
    }

    /**
     * The archival path under test. It mirrors `SolidInvoice\CoreBundle\Pdf\Generator`
     * apart from the PDF/A deltas the SOL-24 spike named.
     *
     * @return array{pdf: string|null, warnings: list<string>}
     */
    private function generateArchival(string $html, bool $auto): array
    {
        $mpdf = new Mpdf([
            'tempDir' => self::getContainer()->getParameter('kernel.cache_dir') . '/pdf',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
            'default_font' => 'helvetica',
            'PDFA' => true,
            'PDFAversion' => '3-B',
            'PDFAauto' => $auto,
        ]);

        $mpdf->allow_charset_conversion = false;
        // A watermark draws with an alpha, which needs a transparency group.
        $mpdf->showWatermarkText = false;
        $mpdf->SetDisplayMode('fullpage');
        // No SetProtection(): PDF/A forbids encryption.
        $mpdf->SetAssociatedFiles([[
            'name' => 'factur-x.xml',
            'mime' => 'text/xml',
            'description' => 'Factur-X Invoice',
            'AFRelationship' => 'Alternative',
            'content' => self::FACTUR_X_XML,
        ]]);
        $mpdf->SetAdditionalXmpRdf(self::XMP_RDF);
        $mpdf->WriteHTML($html);

        // With `PDFAauto => false` mpdf throws instead of emitting a file. Either way
        // `PDFAXwarnings` holds what it found, and a silent substitution is exactly
        // what this run has to surface.
        try {
            $pdf = $mpdf->Output(null, Destination::STRING_RETURN);
        } catch (MpdfException) {
            $pdf = null;
        }

        return ['pdf' => $pdf, 'warnings' => array_values(array_unique($mpdf->PDFAXwarnings))];
    }

    private static function nameFor(string $template): string
    {
        return 'standalone' === $template || str_contains($template, '/Pdf/')
            ? 'standalone'
            : basename(dirname($template));
    }

    private function seedCompanyLogo(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        $setting = $em->getRepository(Setting::class)->findOneBy(['key' => 'system/company/logo']);
        self::assertInstanceOf(Setting::class, $setting, 'system/company/logo setting not seeded');

        $setting->setValue('png|iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $em->flush();
    }

    /**
     * @throws MathException
     */
    private function createFixtureInvoice(): Invoice
    {
        $this->seedCompanyLogo();

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

        $lines = [];

        for ($number = 1; $number <= 2; ++$number) {
            $lines[] = new Line()
                ->setName(sprintf('Sample line item %d', $number))
                ->setDescription('Two rounds of revisions included.')
                ->setPrice(BigInteger::of(75000))
                ->setQty(2)
                ->setTotal(BigInteger::of(150000));
        }

        $total = BigInteger::of(300000);

        return InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'invoiceId' => 'INV-FIXTURE-001',
            'due' => CarbonImmutable::now()->addDays(14),
            'paidDate' => null,
            'archived' => null,
            'terms' => 'Payment due within 30 days.',
            'notes' => 'Thank you for your business.',
            'balance' => $total,
            'total' => $total,
            'baseTotal' => $total,
            'tax' => BigInteger::of(0),
            'discount' => new Discount()
                ->setType(null),
            'lines' => $lines,
            'users' => [$contact],
        ]);
    }
}
