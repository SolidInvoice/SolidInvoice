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
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Twig\Extension\InvoiceTemplateExtension;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use function preg_match;
use function preg_quote;

#[CoversClass(InvoiceTemplateExtension::class)]
final class TemplatesRenderingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private const array CHANNELS = ['pdf', 'email', 'preview'];

    /**
     * Slugs are discovered from the filesystem so a newly added template
     * directory is covered automatically.
     *
     * @return list<string>
     */
    private static function slugs(): array
    {
        $slugs = [];

        foreach (glob(dirname(__DIR__, 3) . '/Resources/views/Templates/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $slug = basename($directory);

            if (! str_starts_with($slug, '_')) {
                $slugs[] = $slug;
            }
        }

        sort($slugs);

        self::assertNotEmpty($slugs, 'No invoice design templates found');

        return $slugs;
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function templateProvider(): iterable
    {
        foreach (self::slugs() as $slug) {
            foreach (self::CHANNELS as $channel) {
                yield sprintf('%s/%s', $slug, $channel) => [$slug, $channel];
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

        self::assertNotEmpty($output, sprintf('Template %s/%s produced empty output', $slug, $channel));
        self::assertStringContainsString($invoice->getInvoiceId(), $output);
        self::assertStringContainsString((string) $invoice->getClient(), $output);

        match ($channel) {
            // PDF and preview render every line item and the company logo —
            // assert both surface so a regression that drops
            // `{% for line in invoice.lines %}` or the logo block is caught.
            'pdf' => $this->assertChannelContains($output, ['</html>', 'Sample line item', 'Two rounds of revisions included.', 'data:image/png;base64']),
            'preview' => $this->assertChannelContains($output, ['Sample line item', 'Two rounds of revisions included.', 'data:image/png;base64']),
            // Email is a summary (totals only, no per-line breakdown), so we
            // verify the schema.org payload + the displayed total instead.
            'email' => $this->assertChannelContains($output, ['schema.org', '$1,500.00']),
            default => self::fail('Unknown channel: ' . $channel),
        };
    }

    /**
     * @param list<string> $needles
     */
    private function assertChannelContains(string $output, array $needles): void
    {
        foreach ($needles as $needle) {
            self::assertStringContainsString($needle, $output);
        }
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
        foreach (self::slugs() as $slug) {
            yield $slug => [$slug];
        }
    }

    /**
     * A 1x1 transparent PNG so every template's guarded logo block renders.
     */
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
     * @param positive-int $lineCount
     * @throws MathException
     */
    private function createFixtureInvoice(
        ?string $description = 'Two rounds of revisions included.',
        ?CarbonImmutable $due = null,
        InvoiceStatus $status = InvoiceStatus::Pending,
        int $lineCount = 1,
        bool $withDue = true,
    ): Invoice {
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

        for ($number = 1; $number <= $lineCount; ++$number) {
            $lines[] = new Line()
                ->setName(1 === $number ? 'Sample line item' : sprintf('Sample line item %d', $number))
                ->setDescription($description)
                ->setPrice(BigInteger::of(75000))
                ->setQty(2)
                ->setTotal(BigInteger::of(150000));
        }

        $total = BigInteger::of(150000)->multipliedBy($lineCount);

        return InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => $status,
            'invoiceId' => 'INV-FIXTURE-001',
            'due' => $withDue ? ($due ?? CarbonImmutable::now()->addDays(14)) : null,
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

    /**
     * `#94a3b8` (`--swp-text-light`) measures 2.56:1 on white, which fails WCAG 2.1 AA.
     * The `modern` PDF used it for the document label and the four line-item table
     * headers. Those carry meaning, so they now use `#475569` (7.58:1) and
     * `#64748b` (4.76:1).
     */
    public function testModernPdfDoesNotUseTextLightForMeaningfulText(): void
    {
        $output = $this->renderPdf('modern');

        self::assertStringNotContainsString('#94a3b8', $output);
        self::assertStringContainsString('#475569', $output);
        self::assertStringContainsString('#64748b', $output);
    }

    /**
     * The `compact` PDF prints `#94a3b8` inside its `#0f172a` header band, where the
     * same colour measures 6.96:1 and passes. It must survive the `modern` fix.
     */
    public function testCompactPdfKeepsTextLightInsideTheDarkBand(): void
    {
        self::assertStringContainsString('#94a3b8', $this->renderPdf('compact'));
    }

    /**
     * Two line items, so every column of the line-item table renders.
     */
    private function renderPdf(string $slug, InvoiceStatus $status = InvoiceStatus::Pending, bool $withDue = true): string
    {
        $invoice = $this->createFixtureInvoice(status: $status, lineCount: 2, withDue: $withDue);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/pdf.html.twig', $slug),
            ['invoice' => $invoice]
        );
    }

    /**
     * The description is optional, so the block that renders it has to disappear entirely
     * for a line that has only a name — not leave an empty div under every item.
     */
    #[DataProvider('lineChannelProvider')]
    public function testTemplateOmitsAnEmptyDescription(string $slug, string $channel): void
    {
        $invoice = $this->createFixtureInvoice(null);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/%s.html.twig', $slug, $channel),
            ['invoice' => $invoice]
        );

        self::assertStringContainsString('Sample line item', $output);
        self::assertStringNotContainsString('line-item-description', $output);
    }

    /**
     * A line whose description is the name over again — what a client that only knows about
     * `description` produces — must print the text once, not twice.
     */
    #[DataProvider('lineChannelProvider')]
    public function testTemplateOmitsADescriptionThatRepeatsTheName(string $slug, string $channel): void
    {
        $invoice = $this->createFixtureInvoice('Sample line item');

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/%s.html.twig', $slug, $channel),
            ['invoice' => $invoice]
        );

        self::assertStringContainsString('Sample line item', $output);
        self::assertStringNotContainsString('line-item-description', $output);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function lineChannelProvider(): iterable
    {
        // Only the channels that render a line breakdown; email is a totals summary.
        foreach (self::slugs() as $slug) {
            foreach (['pdf', 'preview'] as $channel) {
                yield sprintf('%s/%s', $slug, $channel) => [$slug, $channel];
            }
        }
    }

    /**
     * `Pdf/invoice.html.twig` is the standalone document. It prints the due-date
     * urgency hint inline rather than through a macro, and it always prints on
     * white, so it carries the AA-safe light literals.
     *
     * The offset is `days + 6 hours` so the floor division in the template lands
     * on `days` and not on `days - 1` when it renders.
     *
     * @return iterable<string, array{int, string, string}>
     */
    public static function urgencyProvider(): iterable
    {
        yield 'overdue' => [-3, 'OVERDUE BY 3 DAYS', '#b91c1c'];
        yield 'due today' => [0, 'DUE TODAY', '#92400e'];
        yield 'due in 3 days' => [3, 'Due in 3 days', '#92400e'];
        yield 'due in 14 days' => [14, 'Due in 14 days', '#475569'];
    }

    #[DataProvider('urgencyProvider')]
    public function testStandaloneDocumentUsesTheLightPalette(int $days, string $label, string $expected): void
    {
        $invoice = $this->createFixtureInvoice(
            due: CarbonImmutable::now()->addDays($days)->addHours(6)
        );

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice]);

        $matched = preg_match(
            '#<span style="([^"]*)">\s*' . preg_quote($label, '#') . '#',
            $output,
            $matches
        );

        self::assertSame(1, $matched, sprintf('No urgency indicator found for label "%s"', $label));
        self::assertStringContainsString(sprintf('color: %s;', $expected), $matches[1]);

        // The fill colour this issue retires must not come back.
        self::assertStringNotContainsString('#f59e0b', $output);
    }

    /**
     * A paid invoice has nothing outstanding, so it prints no urgency hint at
     * all — not a hint in a different colour.
     */
    public function testAPaidInvoicePrintsNoUrgencyIndicator(): void
    {
        $invoice = $this->createFixtureInvoice(
            due: CarbonImmutable::now()->subDays(3),
            status: InvoiceStatus::Paid
        );

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice]);

        self::assertStringNotContainsString('OVERDUE BY', $output);
    }

    /**
     * The six templates `status_pair` was added to directly. `editorial` and
     * `friendly` are sequenced behind SOL-57 and carry no status line yet;
     * the two `Pdf/*.html.twig` default documents are SOL-48's.
     */
    private const array FREE_TEMPLATE_SLUGS = ['classic', 'modern', 'studio', 'photographer', 'compact', 'monochrome'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function freeTemplateSlugProvider(): iterable
    {
        foreach (self::FREE_TEMPLATE_SLUGS as $slug) {
            yield $slug => [$slug];
        }
    }

    /**
     * The status line is a labelled fact in the meta block, not the watermark.
     * Every one of the six templates must print the translated "Status" label
     * and the translated status word, and never the raw catalog key.
     */
    #[DataProvider('freeTemplateSlugProvider')]
    public function testStatusLineRendersTheTranslatedLabelAndWord(string $slug): void
    {
        $output = $this->renderPdf($slug);

        $matched = preg_match(
            '#font-size: 8pt; color: [^;]+; text-transform: uppercase; letter-spacing: 0.5px;">Status<#',
            $output
        );
        self::assertSame(1, $matched, sprintf('%s does not render the translated "Status" label', $slug));

        $this->assertStatusWordRenders($output, 'Pending', $slug);
    }

    /**
     * Rule 2 of the design spec: the status is never inside the
     * `{% if invoice.due %}` guard. An invoice with no due date must still
     * render its status — the regression SOL-360 §0 C1 is about, and the
     * reason `createFixtureInvoice()` gained `withDue`.
     */
    #[DataProvider('freeTemplateSlugProvider')]
    public function testStatusLineRendersWithNoDueDate(string $slug): void
    {
        $output = $this->renderPdf($slug, withDue: false);

        $this->assertStatusWordRenders($output, 'Pending', $slug);
    }

    /**
     * `monochrome` always overrides the ink to `#1a1a1a`, so the word itself —
     * not a fixed colour — is what the other slugs' assertions can rely on.
     */
    private function assertStatusWordRenders(string $output, string $word, string $slug): void
    {
        $matched = preg_match(
            sprintf('#font-size: 10pt; font-weight: 600; color: [^;]+;">%s<#', preg_quote($word, '#')),
            $output
        );
        self::assertSame(1, $matched, sprintf('%s does not render the status word "%s"', $slug, $word));
    }

    /**
     * The literal string `pdf.status` prints only when the catalog is missing
     * the key. Asserting its absence on every free template is what would
     * have caught a key left out of `translations/messages.en.yml`.
     */
    #[DataProvider('freeTemplateSlugProvider')]
    public function testStatusLineLabelKeyIsTranslated(string $slug): void
    {
        self::assertStringNotContainsString('pdf.status', $this->renderPdf($slug));
    }

    /**
     * One case per ink rather than one per status: `paid` and `overdue` share
     * no ink with `pending` or `draft`, so the four together exercise every
     * branch of the macro's ink map that `monochrome` does not override.
     *
     * @return iterable<string, array{string, InvoiceStatus, string}>
     */
    public static function statusInkProvider(): iterable
    {
        $cases = [
            'paid' => [InvoiceStatus::Paid, '#047857'],
            'overdue' => [InvoiceStatus::Overdue, '#b91c1c'],
            'pending' => [InvoiceStatus::Pending, '#92400e'],
            'draft' => [InvoiceStatus::Draft, '#475569'],
        ];

        foreach (self::FREE_TEMPLATE_SLUGS as $slug) {
            if ('monochrome' === $slug) {
                // monochrome flattens every status to its ink override; see
                // testMonochromeStatusLineIgnoresTheVariantInk.
                continue;
            }

            foreach ($cases as $name => [$status, $ink]) {
                yield sprintf('%s/%s', $slug, $name) => [$slug, $status, $ink];
            }
        }
    }

    #[DataProvider('statusInkProvider')]
    public function testStatusLineUsesTheVariantInk(string $slug, InvoiceStatus $status, string $ink): void
    {
        $output = $this->renderPdf($slug, $status);

        self::assertStringContainsString(sprintf('font-size: 10pt; font-weight: 600; color: %s;', $ink), $output);
    }

    /**
     * monochrome's identity is ink on cream: the status line always renders
     * `#1a1a1a`, even for `Paid`, whose variant ink would otherwise be the
     * success green.
     */
    public function testMonochromeStatusLineIgnoresTheVariantInk(): void
    {
        $output = $this->renderPdf('monochrome', InvoiceStatus::Paid);

        self::assertStringContainsString('font-size: 10pt; font-weight: 600; color: #1a1a1a;">Paid', $output);
        self::assertStringNotContainsString('font-size: 10pt; font-weight: 600; color: #047857;', $output);
    }

    /**
     * Revision 1 of the design put compact's status inside the `#0f172a`
     * header band, where the variant inks fail contrast. It now renders on the
     * white page body instead, as a third cell beside `from_block` and
     * `bill_to_block`, and must not disturb the band's own light-on-dark
     * literal that `testCompactPdfKeepsTextLightInsideTheDarkBand` guards.
     *
     * Splitting on the dark band's own closing `</table>` (the first one in
     * the document) proves the status renders after the band, not merely
     * somewhere in the page — both strings exist in the band too, so a
     * plain "contains" assertion on the full output would not catch a
     * regression that moved the status back inside it.
     */
    public function testCompactStatusLineRendersOutsideTheDarkBand(): void
    {
        $output = $this->renderPdf('compact');

        [$darkBand, $pageBody] = explode('</table>', $output, 2);

        self::assertStringContainsString('#94a3b8', $darkBand);
        self::assertStringNotContainsString('font-size: 10pt; font-weight: 600; color: #92400e;">Pending', $darkBand);
        self::assertStringContainsString('font-size: 10pt; font-weight: 600; color: #92400e;">Pending', $pageBody);
    }

    /**
     * `#94704a` carried both the warm label colour and, on three money lines,
     * brown where the Money-Color Rule wants neutral. The sweep repoints the
     * labels to `#7c4a1e` and the money lines to `#1e293b`; the raw literal
     * must not survive anywhere in either slug.
     *
     * @return iterable<string, array{string}>
     */
    public static function warmLabelSlugProvider(): iterable
    {
        yield 'editorial' => ['editorial'];
        yield 'friendly' => ['friendly'];
    }

    #[DataProvider('warmLabelSlugProvider')]
    public function testEditorialAndFriendlyDropTheOldWarmLabelLiteral(string $slug): void
    {
        $output = $this->renderPdf($slug);

        self::assertStringNotContainsString('#94704a', $output);
        self::assertStringContainsString('#7c4a1e', $output);
    }

    /**
     * `editorial`'s balance row only renders when the invoice has a captured
     * payment against a positive balance (`invoice_has_outstanding_balance()`).
     * The default fixture has no payment, so this is the one case that needs
     * its own fixture to exercise the Money-Color fix at all.
     */
    public function testEditorialBalanceIsNeutralNotWarmBrown(): void
    {
        $invoice = $this->createFixtureInvoice(lineCount: 2);
        $this->addCapturedPayment($invoice);

        $output = $this->renderInvoicePdf('editorial', $invoice);

        // Proves the balance branch itself rendered, not the total branch:
        // without a captured payment on Invoice's own $payments collection,
        // invoice_has_outstanding_balance() is false and both colour
        // assertions below would pass against the unrelated total cell.
        self::assertStringContainsString('Outstanding Balance', $output);
        self::assertStringContainsString('font-family: Georgia, serif; color: #1e293b;', $output);
        self::assertStringNotContainsString('font-family: Georgia, serif; color: #7c4a1e;', $output);
    }

    /**
     * `friendly`'s balance cell is the same fixture requirement as editorial's,
     * on the Courier mono family friendly uses instead of Georgia.
     */
    public function testFriendlyBalanceIsNeutralNotWarmBrown(): void
    {
        $invoice = $this->createFixtureInvoice(lineCount: 2);
        $this->addCapturedPayment($invoice);

        $output = $this->renderInvoicePdf('friendly', $invoice);

        self::assertStringContainsString('Outstanding Balance', $output);
        self::assertStringContainsString('font-family: Courier New, monospace; color: #1e293b;', $output);
        self::assertStringNotContainsString('font-family: Courier New, monospace; color: #7c4a1e;', $output);
    }

    /**
     * `payment_cta` is invisible unless a non-internal payment method is
     * configured and the invoice is not yet Paid (`_macros.html.twig:227`).
     * Its fill is the one `#94704a` use in this sweep that is a background,
     * not text — same repoint, different contrast direction.
     */
    public function testEditorialPaymentButtonUsesTheWarmFillNotBrown(): void
    {
        $invoice = $this->createFixtureInvoice(lineCount: 2);
        PaymentMethodFactory::createOne([
            'company' => $this->company,
            'enabled' => true,
            'internal' => false,
        ]);

        $output = $this->renderInvoicePdf('editorial', $invoice);

        self::assertStringContainsString('background-color: #7c4a1e; padding: 10px 20px; border-radius: 6px;', $output);
    }

    private function addCapturedPayment(Invoice $invoice): void
    {
        $method = PaymentMethodFactory::createOne([
            'company' => $this->company,
            'enabled' => true,
            'internal' => false,
        ]);

        $payment = PaymentFactory::createOne([
            'company' => $this->company,
            'method' => $method,
            'status' => PaymentStatus::Captured,
            'totalAmount' => 50000,
            'currencyCode' => 'USD',
        ]);

        // PaymentFactory only sets Payment's own `invoice` property. Invoice's
        // $payments collection is the inverse side, so it needs addPayment()
        // too, or invoice_has_outstanding_balance() never finds this payment
        // and the balance row never renders.
        $invoice->addPayment($payment);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $em->flush();
    }

    private function renderInvoicePdf(string $slug, Invoice $invoice): string
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/pdf.html.twig', $slug),
            ['invoice' => $invoice]
        );
    }
}
