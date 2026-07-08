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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Action\TemplatePreviewAction;
use SolidInvoice\SaasBundle\Templates\PreviewInvoiceFactory;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use function filter_var;
use function is_bool;

#[Group('functional')]
final class TemplatePreviewActionTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): SaasTestKernel
    {
        $env = $options['environment'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
        $debugRaw = $options['debug'] ?? $_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? true;
        $debug = is_bool($debugRaw)
            ? $debugRaw
            : filter_var((string) $debugRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        return new SaasTestKernel($env, $debug);
    }

    public function testRendersEveryDiscoveredTemplateWithSampleData(): void
    {
        $registry = self::getContainer()->get(BillingTemplateRegistry::class);
        $action = $this->createAction($registry);

        self::assertNotEmpty($registry->getSlugs());

        foreach ($registry->getSlugs() as $slug) {
            $response = $action($slug);

            self::assertSame(200, $response->getStatusCode(), $slug);
            self::assertStringContainsString('INV-2025-0042', (string) $response->getContent(), $slug);
            self::assertStringContainsString('Acme Studios', (string) $response->getContent(), $slug);
        }
    }

    public function testRendersTheDefaultTemplate(): void
    {
        $action = $this->createAction(self::getContainer()->get(BillingTemplateRegistry::class));

        $response = $action(BillingTemplateRegistry::DEFAULT_SLUG);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('INV-2025-0042', (string) $response->getContent());
        // The default is an mPDF page document; the preview must constrain it
        // to a sheet instead of letting it stretch edge-to-edge.
        self::assertStringContainsString('max-width: 800px', (string) $response->getContent());
    }

    public function testRejectsUnknownTemplates(): void
    {
        $action = $this->createAction(self::getContainer()->get(BillingTemplateRegistry::class));

        $this->expectException(NotFoundHttpException::class);

        $action('does-not-exist');
    }

    private function createAction(BillingTemplateRegistry $registry): TemplatePreviewAction
    {
        return new TemplatePreviewAction(
            $registry,
            new PreviewInvoiceFactory(self::getContainer()->get(SystemConfig::class)),
            self::getContainer()->get(Environment::class),
        );
    }
}
