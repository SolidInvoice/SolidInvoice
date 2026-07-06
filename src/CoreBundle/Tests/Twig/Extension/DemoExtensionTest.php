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
use SolidInvoice\CoreBundle\Demo\DemoMode;
use SolidInvoice\CoreBundle\Twig\Extension\DemoExtension;
use SolidWorx\Toggler\ToggleInterface;

/**
 * NOTE: DemoMode is a final class, and this project's PHPUnit version (13.x)
 * refuses to double final classes. Following the same pattern already used in
 * DemoModeTest, we construct a real DemoMode with a mocked ToggleInterface
 * rather than mocking DemoMode itself.
 */
final class DemoExtensionTest extends TestCase
{
    public function testDemoEnabledDelegatesToDemoMode(): void
    {
        $toggle = $this->createMock(ToggleInterface::class);
        $toggle->expects(self::once())
            ->method('isActive')
            ->with('demo_enabled')
            ->willReturn(true);

        $demoMode = new DemoMode($toggle);

        self::assertTrue((new DemoExtension($demoMode))->isEnabled());
    }

    public function testGettersDelegateToDemoMode(): void
    {
        $demoMode = new DemoMode(
            $this->createMock(ToggleInterface::class),
            'demo',
            'secret',
            'https://example.test/signup',
        );

        $extension = new DemoExtension($demoMode);

        self::assertSame('demo', $extension->username());
        self::assertSame('secret', $extension->password());
        self::assertSame('https://example.test/signup', $extension->signupUrl());
    }

    public function testExposesFourTwigFunctions(): void
    {
        $demoMode = new DemoMode($this->createMock(ToggleInterface::class));

        $names = array_map(
            static fn ($fn): string => $fn->getName(),
            (new DemoExtension($demoMode))->getFunctions(),
        );

        self::assertSame(
            ['demo_enabled', 'demo_username', 'demo_password', 'demo_signup_url'],
            $names,
        );
    }
}
