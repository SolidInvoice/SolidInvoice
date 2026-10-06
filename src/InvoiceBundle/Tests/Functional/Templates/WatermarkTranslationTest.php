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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\Loader\ArrayLoader as TranslationArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use function htmlspecialchars_decode;
use function mb_strtoupper;

/**
 * SOL-48: the PDF status watermark must print the translated label, not the
 * raw enum backing value, so it stays readable in every locale.
 */
final class WatermarkTranslationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testLegacyPdfWatermarkUsesTheTranslatedLabel(): void
    {
        $this->assertWatermarkIsTranslated('@SolidInvoiceInvoice/Pdf/invoice.html.twig');
    }

    public function testTemplatesPdfBaseWatermarkUsesTheTranslatedLabel(): void
    {
        // Templates/_pdf_base.html.twig carries the same watermark block as the
        // legacy Pdf/invoice.html.twig and is reached by every designed template
        // (classic, modern, ...). Rendering one of them exercises that shared base.
        $this->assertWatermarkIsTranslated('@SolidInvoiceInvoice/Templates/classic/pdf.html.twig');
    }

    private function assertWatermarkIsTranslated(string $template): void
    {
        $invoice = $this->createFixtureInvoice(InvoiceStatus::Paid);

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $expectedLabel = mb_strtoupper($translator->trans('status.' . InvoiceStatus::Paid->value));

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render($template, ['invoice' => $invoice]);

        self::assertStringContainsString(
            sprintf('<watermarktext content="%s" alpha="0.08"/>', $expectedLabel),
            $output,
        );
    }

    /**
     * mPDF's <watermarktext> tag handler calls htmlspecialchars_decode($attr['CONTENT'], ENT_QUOTES)
     * (vendor/mpdf/mpdf/src/Tag/WatermarkText.php) before setting the watermark text, so a
     * translated label containing an apostrophe is safe: Twig HTML-escapes it going in, and
     * mPDF decodes it going out. Proven here against an isolated translator and Twig
     * environment, since the app has no second locale to exercise the real one with yet.
     */
    public function testApostropheInATranslatedLabelRoundTripsCorrectly(): void
    {
        $label = "Payée à l'étude";

        $translator = new Translator('fr');
        $translator->addLoader('array', new TranslationArrayLoader());
        $translator->addResource('array', ['status.paid' => $label], 'fr', 'messages');

        $twig = new Environment(new TwigArrayLoader([
            'watermark' => '<watermarktext content="{{ (\'status.\' ~ status)|trans|upper }}" alpha="0.08"/>',
        ]));
        $twig->addExtension(new TranslationExtension($translator));

        $output = $twig->render('watermark', ['status' => 'paid']);
        $escapedLabel = mb_strtoupper(htmlspecialchars($label, ENT_QUOTES));

        // Twig HTML-escapes the apostrophe for the attribute context ...
        self::assertStringContainsString(sprintf('content="%s"', $escapedLabel), $output);
        // ... and mPDF's own tag handler decodes exactly that escaping back out
        // (vendor/mpdf/mpdf/src/Tag/WatermarkText.php calls
        // htmlspecialchars_decode($attr['CONTENT'], ENT_QUOTES)), so the two
        // together are a safe round trip and no extra escaping is needed here.
        self::assertSame(mb_strtoupper($label), htmlspecialchars_decode($escapedLabel, ENT_QUOTES));
    }

    private function createFixtureInvoice(InvoiceStatus $status): Invoice
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company]);

        return InvoiceFactory::createOne([
            'company' => $company,
            'client' => $client,
            'status' => $status,
            'total' => BigInteger::of(150000),
        ]);
    }
}
