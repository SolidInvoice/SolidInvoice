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
            'due' => $due ?? CarbonImmutable::now()->addDays(14),
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
    private function renderPdf(string $slug): string
    {
        $invoice = $this->createFixtureInvoice(lineCount: 2);

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
}
