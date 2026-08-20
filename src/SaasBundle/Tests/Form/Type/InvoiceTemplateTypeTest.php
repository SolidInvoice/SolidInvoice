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

namespace SolidInvoice\SaasBundle\Tests\Form\Type;

use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\SaasBundle\Form\Type\InvoiceTemplateType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_map;
use function dirname;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(InvoiceTemplateType::class)]
#[AllowMockObjectsWithoutExpectations]
final class InvoiceTemplateTypeTest extends TypeTestCase
{
    private string $invoiceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->invoiceDir = sys_get_temp_dir() . '/' . uniqid('invoice_template_type_', true) . '/invoice';
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);

        foreach (['sleek', 'bold'] as $slug) {
            foreach (['pdf', 'email', 'preview'] as $channel) {
                $this->filesystem->dumpFile(sprintf('%s/%s/%s.html.twig', $this->invoiceDir, $slug, $channel), $slug);
            }
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(dirname($this->invoiceDir));

        parent::tearDown();
    }

    /**
     * @return list<PreloadedExtension>
     */
    #[Override]
    protected function getExtensions(): array
    {
        $registry = new BillingTemplateRegistry([
            BillingDocumentType::Invoice->value => $this->invoiceDir,
        ]);

        return [
            new PreloadedExtension([new InvoiceTemplateType($registry)], []),
        ];
    }

    public function testChoicesAreTheDefaultPlusEveryDiscoveredTemplate(): void
    {
        $view = $this->factory->create(InvoiceTemplateType::class, BillingTemplateRegistry::DEFAULT_SLUG)->createView();

        self::assertSame(
            [BillingTemplateRegistry::DEFAULT_SLUG, 'bold', 'sleek'],
            array_map(static fn ($child): mixed => $child->vars['value'], $view->children),
        );
    }

    public function testDoesNotGrowAPlaceholderChoiceWhenNotRequired(): void
    {
        // The settings form builds every field with required=false
        // (SettingsType); without an explicit placeholder=false that adds an
        // empty placeholder radio, whose empty slug the picker widget cannot
        // build a preview URL for.
        $view = $this->factory->create(InvoiceTemplateType::class, BillingTemplateRegistry::DEFAULT_SLUG, [
            'required' => false,
        ])->createView();

        foreach ($view->children as $child) {
            self::assertNotSame('', $child->vars['value']);
        }

        self::assertCount(3, $view->children);
    }

    public function testSubmittingNothingFallsBackToTheDefaultTemplate(): void
    {
        $form = $this->factory->create(InvoiceTemplateType::class, null, ['required' => false]);

        $form->submit(null);

        self::assertSame(BillingTemplateRegistry::DEFAULT_SLUG, $form->getData());
    }
}
