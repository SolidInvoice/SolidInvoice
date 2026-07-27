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

namespace SolidInvoice\CoreBundle\Tests\Twig;

use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Twig\Extension\ModeExtension;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class DemoBannerTemplateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private function render(ModeResolver $modeResolver): string
    {
        // ModeResolver is a widely-shared private service that is already initialized by the
        // time a test runs, so the container refuses to replace it directly. Overriding the
        // (lazily instantiated) Twig extension that consumes it achieves the same effect.
        self::getContainer()->set(ModeExtension::class, new ModeExtension($modeResolver));

        return self::getContainer()->get(Environment::class)
            ->resolveTemplate('@SolidInvoiceCore/Layout/default.html.twig')
            ->renderBlock('demo_banner', []);
    }

    public function testBannerShownWithSignupCta(): void
    {
        $html = $this->render(new ModeResolver('demo', 'demo@example.com', 'demo-password', 'https://signup.example.com'));

        self::assertStringContainsString('demo-app-banner', $html);
        self::assertStringContainsString('https://signup.example.com', $html);
    }

    public function testBannerShownWithoutSignupCta(): void
    {
        $html = $this->render(new ModeResolver('demo', 'demo@example.com', 'demo-password'));

        self::assertStringContainsString('demo-app-banner', $html);
        self::assertStringNotContainsString('demo-app-banner-cta', $html);
    }

    public function testNoBannerWhenNotInDemoMode(): void
    {
        self::assertStringNotContainsString('demo-app-banner', $this->render(new ModeResolver()));
    }
}
