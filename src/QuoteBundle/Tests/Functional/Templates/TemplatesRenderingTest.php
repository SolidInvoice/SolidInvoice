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
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use function array_keys;
use function basename;
use function dirname;
use function glob;
use function in_array;
use function preg_match;
use function preg_quote;
use function sort;
use function sprintf;
use function str_starts_with;

/**
 * Renders every quote design template under `Resources/views/Templates` so a
 * newly added template directory is covered automatically.
 */
final class TemplatesRenderingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private const array CHANNELS = ['pdf', 'email', 'preview'];

    /**
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

        self::assertNotEmpty($slugs, 'No quote design templates found');

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
        $quote = $this->createFixtureQuote();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/%s.html.twig', $slug, $channel),
            ['quote' => $quote]
        );

        self::assertNotEmpty($output, sprintf('Template %s/%s produced empty output', $slug, $channel));
        self::assertStringContainsString($quote->getQuoteId(), $output);

        match ($channel) {
            // PDF and preview render every line item, the client block and the
            // company logo — assert all surface so a regression that drops
            // `{% for line in quote.lines %}` or the logo block is caught.
            'pdf' => $this->assertChannelContains($output, ['</html>', 'Sample line item', 'Two rounds of revisions included.', (string) $quote->getClient(), 'data:image/png;base64']),
            'preview' => $this->assertChannelContains($output, ['Sample line item', 'Two rounds of revisions included.', (string) $quote->getClient(), 'data:image/png;base64']),
            // Email is a summary addressed to the client (totals only, no
            // per-line breakdown or client block), so we verify the schema.org
            // payload + the displayed total instead.
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

        $quote = $this->createFixtureQuote();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/pdf.html.twig', $slug),
            ['quote' => $quote]
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
    private function createFixtureQuote(
        ?string $description = 'Two rounds of revisions included.',
        ?CarbonImmutable $due = null,
        QuoteStatus $status = QuoteStatus::Pending,
        int $lineCount = 1,
        bool $withDue = true,
    ): Quote {
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

        return QuoteFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => $status,
            'quoteId' => 'QUOTE-FIXTURE-001',
            'due' => $withDue ? ($due ?? CarbonImmutable::now()->addDays(14)) : null,
            'archived' => null,
            'terms' => 'Valid for 14 days.',
            'notes' => 'Thank you for your interest.',
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
    private function renderPdf(string $slug, QuoteStatus $status = QuoteStatus::Pending, bool $withDue = true): string
    {
        $quote = $this->createFixtureQuote(status: $status, lineCount: 2, withDue: $withDue);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/pdf.html.twig', $slug),
            ['quote' => $quote]
        );
    }

    /**
     * The description is optional, so the block that renders it has to disappear entirely
     * for a line that has only a name — not leave an empty div under every item.
     */
    #[DataProvider('lineChannelProvider')]
    public function testTemplateOmitsAnEmptyDescription(string $slug, string $channel): void
    {
        $quote = $this->createFixtureQuote(null);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/%s.html.twig', $slug, $channel),
            ['quote' => $quote]
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
        $quote = $this->createFixtureQuote('Sample line item');

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/%s.html.twig', $slug, $channel),
            ['quote' => $quote]
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
     * Templates whose `validity_indicator` call sits on a dark band and so passes
     * its own palette. Every other template prints the indicator on light paper
     * and takes the macro default.
     */
    private const array DARK_PAPER_SLUGS = ['compact'];

    private const array LIGHT_PALETTE = ['danger' => '#b91c1c', 'urgent' => '#92400e', 'quiet' => '#475569'];

    private const array DARK_PALETTE = ['danger' => '#fca5a5', 'urgent' => '#fbbf24', 'quiet' => '#cbd5e1'];

    /**
     * The offset is `days + 6 hours` so the floor division in the macro lands on
     * `days` and not on `days - 1` when the template renders.
     */
    private const array URGENCY_STATES = [
        'expired' => ['days' => -3, 'label' => 'EXPIRED', 'tone' => 'danger'],
        'expires today' => ['days' => 0, 'label' => 'EXPIRES TODAY', 'tone' => 'urgent'],
        'expires in 3 days' => ['days' => 3, 'label' => 'Valid for 3 days', 'tone' => 'urgent'],
        'expires in 14 days' => ['days' => 14, 'label' => 'Valid for 14 days', 'tone' => 'quiet'],
    ];

    /**
     * The urgency colour has to stay readable on the paper the template actually
     * prints on, so the macro takes a palette instead of a fixed colour. A
     * blanket repoint of the macro literals breaks one paper or the other.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function urgencyProvider(): iterable
    {
        foreach (self::slugs() as $slug) {
            foreach (array_keys(self::URGENCY_STATES) as $state) {
                yield sprintf('%s/%s', $slug, $state) => [$slug, $state];
            }
        }
    }

    #[DataProvider('urgencyProvider')]
    public function testUrgencyIndicatorUsesThePaletteForItsPaper(string $slug, string $state): void
    {
        $case = self::URGENCY_STATES[$state];

        $quote = $this->createFixtureQuote(
            due: CarbonImmutable::now()->addDays($case['days'])->addHours(6)
        );

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceQuote/Templates/%s/pdf.html.twig', $slug),
            ['quote' => $quote]
        );

        $palette = in_array($slug, self::DARK_PAPER_SLUGS, true) ? self::DARK_PALETTE : self::LIGHT_PALETTE;
        $unexpected = in_array($slug, self::DARK_PAPER_SLUGS, true) ? self::LIGHT_PALETTE : self::DARK_PALETTE;

        $style = $this->urgencyStyle($output, $case['label']);

        self::assertStringContainsString(
            sprintf('color: %s;', $palette[$case['tone']]),
            $style,
            sprintf('%s prints the %s indicator in the wrong palette', $slug, $state)
        );

        self::assertStringNotContainsString($unexpected[$case['tone']], $style);

        // The fill colour this issue retires must not come back on any paper.
        self::assertStringNotContainsString('#f59e0b', $output);
    }

    /**
     * An accepted quote has nothing left to decide, so it prints no urgency hint
     * at all — not a hint in a different colour.
     */
    public function testAnAcceptedQuotePrintsNoUrgencyIndicator(): void
    {
        $quote = $this->createFixtureQuote(
            due: CarbonImmutable::now()->subDays(3),
            status: QuoteStatus::Accepted
        );

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        foreach (self::slugs() as $slug) {
            $output = $twig->render(
                sprintf('@SolidInvoiceQuote/Templates/%s/pdf.html.twig', $slug),
                ['quote' => $quote]
            );

            self::assertStringNotContainsString('EXPIRED', $output, $slug . ' prints an indicator on an accepted quote');
        }
    }

    /**
     * `Pdf/quote.html.twig` is the standalone document. It has no macro to
     * parameterise and always prints on white, so it carries the light literals.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function standaloneUrgencyProvider(): iterable
    {
        foreach (self::URGENCY_STATES as $state => $case) {
            yield $state => [$state, self::LIGHT_PALETTE[$case['tone']]];
        }
    }

    #[DataProvider('standaloneUrgencyProvider')]
    public function testStandaloneDocumentUsesTheLightPalette(string $state, string $expected): void
    {
        $case = self::URGENCY_STATES[$state];

        $quote = $this->createFixtureQuote(
            due: CarbonImmutable::now()->addDays($case['days'])->addHours(6)
        );

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote]);

        self::assertStringContainsString(
            sprintf('color: %s;', $expected),
            $this->urgencyStyle($output, $case['label'])
        );

        self::assertStringNotContainsString('#f59e0b', $output);
    }

    /**
     * `due` hydrates at midnight (`date_immutable`), so this fixes it to midnight
     * too instead of `addHours(6)` like {@see URGENCY_STATES}. A quote due today,
     * rendered any time after midnight, must read "EXPIRES TODAY" and never
     * "EXPIRED" — the regression this issue fixes prints the latter for every
     * hour of the due date itself.
     */
    public function testAQuoteDueTodayIsNotMarkedExpired(): void
    {
        $quote = $this->createFixtureQuote(due: CarbonImmutable::now()->setTime(0, 0));

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote]);

        self::assertStringContainsString('EXPIRES TODAY', $output);
        self::assertStringNotContainsString('EXPIRED', $output);
    }

    /**
     * Returns the `style` attribute of the span that carries the urgency label.
     */
    private function urgencyStyle(string $output, string $label): string
    {
        $matched = preg_match(
            '#<span style="([^"]*)">\s*' . preg_quote($label, '#') . '#',
            $output,
            $matches
        );

        self::assertSame(1, $matched, sprintf('No urgency indicator found for label "%s"', $label));

        return $matches[1];
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
     * `{% if quote.due %}` guard. A quote with no due date must still render
     * its status — the regression SOL-360 §0 C1 is about, and the reason
     * `createFixtureQuote()` gained `withDue`.
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
     * One case per ink rather than one per status: `accepted` and `declined`
     * share no ink with `pending` or `draft`, so the four together exercise
     * every branch of the macro's ink map that `monochrome` does not override.
     *
     * @return iterable<string, array{string, QuoteStatus, string}>
     */
    public static function statusInkProvider(): iterable
    {
        $cases = [
            'accepted' => [QuoteStatus::Accepted, '#047857'],
            'declined' => [QuoteStatus::Declined, '#b91c1c'],
            'pending' => [QuoteStatus::Pending, '#92400e'],
            'draft' => [QuoteStatus::Draft, '#475569'],
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
    public function testStatusLineUsesTheVariantInk(string $slug, QuoteStatus $status, string $ink): void
    {
        $output = $this->renderPdf($slug, $status);

        self::assertStringContainsString(sprintf('font-size: 10pt; font-weight: 600; color: %s;', $ink), $output);
    }

    /**
     * monochrome's identity is ink on cream: the status line always renders
     * `#1a1a1a`, even for `Accepted`, whose variant ink would otherwise be the
     * success green.
     */
    public function testMonochromeStatusLineIgnoresTheVariantInk(): void
    {
        $output = $this->renderPdf('monochrome', QuoteStatus::Accepted);

        self::assertStringContainsString('font-size: 10pt; font-weight: 600; color: #1a1a1a;">Accepted', $output);
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
     * `#94704a` carried both the warm label colour and, on the total cell,
     * brown where the Money-Color Rule wants neutral. The sweep repoints the
     * labels to `#7c4a1e` and the money line to `#1e293b`; the raw literal
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
     * Quotes have no outstanding-balance branch, so the total cell always
     * renders — unlike invoice editorial's balance cell, no special fixture
     * is needed to exercise the Money-Color fix here.
     */
    public function testEditorialTotalIsNeutralNotWarmBrown(): void
    {
        $output = $this->renderPdf('editorial');

        self::assertStringContainsString('font-family: Georgia, serif; color: #1e293b;', $output);
        self::assertStringNotContainsString('font-family: Georgia, serif; color: #7c4a1e;', $output);
    }
}
