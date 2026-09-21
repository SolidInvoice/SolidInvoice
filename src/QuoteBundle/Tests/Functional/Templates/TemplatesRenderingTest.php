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
            'due' => $due ?? CarbonImmutable::now()->addDays(14),
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
    private function renderPdf(string $slug): string
    {
        $quote = $this->createFixtureQuote(lineCount: 2);

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
}
