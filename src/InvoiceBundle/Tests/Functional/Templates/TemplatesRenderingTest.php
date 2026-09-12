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
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Enum\UnitCode;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Twig\Extension\InvoiceTemplateExtension;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

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
        $output = $this->renderTemplate($slug, $channel, $invoice);

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

        $html = $this->renderTemplate($slug, 'pdf', $this->createFixtureInvoice());

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
     * @param list<Line> $lines Replaces the default line outright, for a test that needs to
     *                          dictate the line rather than just its description.
     */
    private function createFixtureInvoice(?string $description = 'Two rounds of revisions included.', array $lines = []): Invoice
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
            'balance' => BigInteger::of(150000),
            'total' => BigInteger::of(150000),
            'baseTotal' => BigInteger::of(150000),
            'tax' => BigInteger::of(0),
            'discount' => new Discount()
                ->setType(null),
            'lines' => $lines !== [] ? $lines : [
                new Line()
                    ->setName('Sample line item')
                    ->setDescription($description)
                    ->setPrice(BigInteger::of(75000))
                    ->setQty(2)
                    ->setTotal(BigInteger::of(150000)),
            ],
            'users' => [$contact],
        ]);
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
     * The unit is an inline suffix on the quantity, not a column, so a template needs no
     * markup of its own for it — but it does have to route the quantity through
     * {@see \SolidInvoice\CoreBundle\Twig\Extension\BillingExtension::quantity()} to show it.
     */
    #[DataProvider('lineChannelProvider')]
    public function testUnitOfMeasureRenders(string $slug, string $channel): void
    {
        $output = $this->renderTemplate($slug, $channel, $this->createFixtureInvoice(lines: [
            new Line()
                ->setName('Consulting')
                ->setPrice(BigInteger::of(12500))
                ->setQty(12)
                ->setUnitCode(UnitCode::HOUR)
                ->setTotal(BigInteger::of(150000)),
        ]));

        self::assertStringContainsString('12 hours', $output);
    }

    /**
     * Nothing about a unit may reach the page when every line bills in plain units, which is
     * every invoice that existed before the column did.
     */
    #[DataProvider('lineChannelProvider')]
    public function testUnitOfMeasureIsSuppressedForPlainUnits(string $slug, string $channel): void
    {
        $output = $this->renderTemplate($slug, $channel, $this->createFixtureInvoice());
        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);

        self::assertStringNotContainsString(
            sprintf('2 %s', UnitCode::UNIT->trans($translator)),
            $output
        );
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

    private function renderTemplate(string $slug, string $channel, Invoice $invoice): string
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/%s.html.twig', $slug, $channel),
            ['invoice' => $invoice]
        );
    }
}
