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

namespace SolidInvoice\CoreBundle\Tests\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use Symfony\Component\Filesystem\Filesystem;
use function dirname;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(BillingTemplateRegistry::class)]
final class BillingTemplateRegistryTest extends TestCase
{
    private string $invoiceDir;

    private string $quoteDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $base = sys_get_temp_dir() . '/' . uniqid('billing_templates_', true);
        $this->invoiceDir = $base . '/invoice';
        $this->quoteDir = $base . '/quote';

        foreach (['sleek', 'bold'] as $slug) {
            foreach (['pdf', 'email', 'preview'] as $channel) {
                $this->filesystem->dumpFile(sprintf('%s/%s/%s.html.twig', $this->invoiceDir, $slug, $channel), $slug);
            }
        }

        // "sleek" has a quote variant, "bold" is invoice-only.
        foreach (['pdf', 'email', 'preview'] as $channel) {
            $this->filesystem->dumpFile(sprintf('%s/sleek/%s.html.twig', $this->quoteDir, $channel), 'sleek');
        }

        // Shared partials and a stray "default" directory must not become slugs.
        $this->filesystem->dumpFile($this->invoiceDir . '/_macros.html.twig', 'macros');
        $this->filesystem->mkdir($this->invoiceDir . '/_partials');
        $this->filesystem->mkdir($this->invoiceDir . '/default');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(dirname($this->invoiceDir));
    }

    private function createRegistry(): BillingTemplateRegistry
    {
        return new BillingTemplateRegistry([
            BillingDocumentType::Invoice->value => $this->invoiceDir,
            BillingDocumentType::Quote->value => $this->quoteDir,
        ]);
    }

    public function testDiscoversSlugsFromTheFilesystem(): void
    {
        self::assertSame(['bold', 'sleek'], $this->createRegistry()->getSlugs());
    }

    public function testHas(): void
    {
        $registry = $this->createRegistry();

        self::assertTrue($registry->has('sleek'));
        self::assertFalse($registry->has('nonexistent'));
        self::assertFalse($registry->has('_partials'));
        self::assertFalse($registry->has('default'));
    }

    public function testChoicesAreHumanised(): void
    {
        self::assertSame(['bold' => 'Bold', 'sleek' => 'Sleek'], $this->createRegistry()->getChoices());
    }

    public function testTemplatePathForExistingVariant(): void
    {
        self::assertSame(
            '@SolidInvoiceInvoice/Templates/sleek/pdf.html.twig',
            $this->createRegistry()->templatePath('sleek', BillingDocumentType::Invoice, BillingTemplateChannel::Pdf),
        );

        self::assertSame(
            '@SolidInvoiceQuote/Templates/sleek/preview.html.twig',
            $this->createRegistry()->templatePath('sleek', BillingDocumentType::Quote, BillingTemplateChannel::View),
        );
    }

    public function testTemplatePathReturnsNullForMissingVariant(): void
    {
        $registry = $this->createRegistry();

        // "bold" ships no quote variant, so quotes must fall back to the default.
        self::assertNull($registry->templatePath('bold', BillingDocumentType::Quote, BillingTemplateChannel::Pdf));
        self::assertNull($registry->templatePath('nonexistent', BillingDocumentType::Invoice, BillingTemplateChannel::Pdf));
    }
}
