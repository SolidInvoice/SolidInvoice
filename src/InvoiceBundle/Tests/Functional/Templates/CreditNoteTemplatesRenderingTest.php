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
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\CreditNoteFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * CN-5: one rendering test per template, asserting the "CREDIT NOTE" title
 * and the corrected invoice's number and issue date both appear.
 */
final class CreditNoteTemplatesRenderingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

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
     * `compact` prints the credit note date as `Y-m-d`; every other template
     * uses the long `F j, Y` form, matching the invoice variants' own split.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function slugProvider(): iterable
    {
        $shortDateFormatSlugs = ['compact'];

        foreach (self::slugs() as $slug) {
            yield $slug => [$slug, in_array($slug, $shortDateFormatSlugs, true) ? 'Y-m-d' : 'F j, Y'];
        }
    }

    #[DataProvider('slugProvider')]
    public function testCreditNoteTemplateRendersTitleAndInvoiceReference(string $slug, string $dateFormat): void
    {
        $creditNote = $this->createFixtureCreditNote();
        $invoice = $creditNote->getInvoice();
        self::assertNotNull($invoice);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->render(
            sprintf('@SolidInvoiceInvoice/Templates/%s/credit_note_pdf.html.twig', $slug),
            ['creditNote' => $creditNote]
        );

        self::assertNotEmpty($output, sprintf('Template %s/credit_note_pdf produced empty output', $slug));
        self::assertStringContainsString('</html>', $output);

        // Unmistakably not an invoice.
        self::assertStringContainsString('CREDIT NOTE', $output);
        self::assertStringNotContainsString('>INVOICE<', $output);

        // The corrected invoice's number and issue date, not the credit note's own date.
        self::assertStringContainsString($invoice->getInvoiceId(), $output);
        self::assertStringContainsString($invoice->getInvoiceDate()->format($dateFormat), $output);

        self::assertStringContainsString('Returned faulty unit', $output);
    }

    private function createFixtureCreditNote(): CreditNote
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'name' => 'Acme Corp',
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'invoiceId' => 'INV-FIXTURE-042',
            'invoiceDate' => CarbonImmutable::parse('2026-03-14'),
        ]);

        $line = new CreditNoteLine()
            ->setName('Returned faulty unit')
            ->setPrice(BigInteger::of(50000))
            ->setQty(1)
            ->setTotal(BigInteger::of(50000));

        return CreditNoteFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'invoice' => $invoice,
            'status' => CreditNoteStatus::Issued,
            'creditNoteId' => 'CN-FIXTURE-001',
            'creditNoteDate' => CarbonImmutable::parse('2026-03-20'),
            'reason' => 'Faulty unit returned by client.',
            'terms' => null,
            'notes' => null,
            'lines' => [$line],
            'total' => BigInteger::of(50000),
            'baseTotal' => BigInteger::of(50000),
            'tax' => BigInteger::of(0),
            'discount' => new Discount()->setType(null),
        ]);
    }
}
