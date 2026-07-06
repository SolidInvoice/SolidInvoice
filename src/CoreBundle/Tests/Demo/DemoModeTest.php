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

namespace SolidInvoice\CoreBundle\Tests\Demo;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Demo\DemoMode;
use SolidWorx\Toggler\ToggleInterface;

final class DemoModeTest extends TestCase
{
    public function testIsEnabledDelegatesToToggle(): void
    {
        $toggle = $this->createMock(ToggleInterface::class);
        $toggle->expects(self::once())
            ->method('isActive')
            ->with('demo_enabled')
            ->willReturn(true);

        $demoMode = new DemoMode($toggle, 'demo', 'secret', 'https://example.test/signup');

        self::assertTrue($demoMode->isEnabled());
    }

    public function testGettersReturnConfiguredValues(): void
    {
        $demoMode = new DemoMode(
            $this->createMock(ToggleInterface::class),
            'demo',
            'secret',
            'https://example.test/signup',
        );

        self::assertSame('demo', $demoMode->username());
        self::assertSame('secret', $demoMode->password());
        self::assertSame('https://example.test/signup', $demoMode->signupUrl());
    }

    public function testEmptyValuesReturnNull(): void
    {
        $demoMode = new DemoMode(
            $this->createMock(ToggleInterface::class),
            '',
            '',
            '',
        );

        self::assertNull($demoMode->username());
        self::assertNull($demoMode->password());
        self::assertNull($demoMode->signupUrl());
    }
}
