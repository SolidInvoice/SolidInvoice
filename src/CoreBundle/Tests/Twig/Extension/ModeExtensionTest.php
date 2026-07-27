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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Twig\Extension\ModeExtension;

final class ModeExtensionTest extends TestCase
{
    public function testDelegatesToModeResolverInSelfHostedMode(): void
    {
        $extension = new ModeExtension(new ModeResolver());

        self::assertSame('self-hosted', $extension->appMode());
        self::assertFalse($extension->isDemo());
        self::assertFalse($extension->isSaas());
        self::assertNull($extension->demoUsername());
        self::assertNull($extension->demoPassword());
        self::assertNull($extension->demoSignupUrl());
    }

    public function testDelegatesToModeResolverInDemoMode(): void
    {
        $resolver = new ModeResolver('demo', 'demo', 'secret', 'https://example.test/signup');
        $extension = new ModeExtension($resolver);

        self::assertSame('demo', $extension->appMode());
        self::assertTrue($extension->isDemo());
        self::assertFalse($extension->isSaas());
        self::assertSame('demo', $extension->demoUsername());
        self::assertSame('secret', $extension->demoPassword());
        self::assertSame('https://example.test/signup', $extension->demoSignupUrl());
    }

    public function testDelegatesToModeResolverInSaasMode(): void
    {
        $extension = new ModeExtension(new ModeResolver('saas'));

        self::assertSame('saas', $extension->appMode());
        self::assertFalse($extension->isDemo());
        self::assertTrue($extension->isSaas());
        self::assertNull($extension->demoUsername());
    }

    public function testExposesSixTwigFunctions(): void
    {
        $names = array_map(
            static fn ($fn): string => $fn->getName(),
            (new ModeExtension(new ModeResolver()))->getFunctions(),
        );

        self::assertSame(
            ['app_mode', 'is_demo', 'is_saas', 'demo_username', 'demo_password', 'demo_signup_url'],
            $names,
        );
    }
}
