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

namespace SolidInvoice\QuoteBundle\Tests\Functional\Templates;

use Brick\Math\BigInteger;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function mb_strtoupper;

/**
 * SOL-48: the PDF status watermark must print the translated label, not the
 * raw enum backing value, so it stays readable in every locale.
 *
 * The apostrophe-escaping / mPDF-decode round trip is proven once, on the
 * InvoiceBundle side (SolidInvoice\InvoiceBundle\Tests\Functional\Templates\WatermarkTranslationTest),
 * since the mechanism is identical for both bundles.
 */
final class WatermarkTranslationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testLegacyPdfWatermarkUsesTheTranslatedLabel(): void
    {
        $this->assertWatermarkIsTranslated('@SolidInvoiceQuote/Pdf/quote.html.twig');
    }

    public function testTemplatesPdfBaseWatermarkUsesTheTranslatedLabel(): void
    {
        // Templates/_pdf_base.html.twig carries the same watermark block as the
        // legacy Pdf/quote.html.twig and is reached by every designed template
        // (classic, modern, ...). Rendering one of them exercises that shared base.
        $this->assertWatermarkIsTranslated('@SolidInvoiceQuote/Templates/classic/pdf.html.twig');
    }

    private function assertWatermarkIsTranslated(string $template): void
    {
        $quote = $this->createFixtureQuote(QuoteStatus::Accepted);

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $expectedLabel = mb_strtoupper($translator->trans('status.' . QuoteStatus::Accepted->value));

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render($template, ['quote' => $quote]);

        self::assertStringContainsString(
            sprintf('<watermarktext content="%s" alpha="0.08"/>', $expectedLabel),
            $output,
        );
    }

    private function createFixtureQuote(QuoteStatus $status): Quote
    {
        $company = CompanyFactory::createOne();
        // Pinned: ClientFactory's default currencyCode is a random ISO code from Faker, which
        // can land on one money/money no longer recognises (e.g. the discontinued CUC). The
        // watermark label this test checks does not depend on currency at all.
        $client = ClientFactory::createOne(['company' => $company, 'currencyCode' => 'USD']);

        return QuoteFactory::createOne([
            'company' => $company,
            'client' => $client,
            'status' => $status,
            'total' => BigInteger::of(150000),
        ]);
    }
}
