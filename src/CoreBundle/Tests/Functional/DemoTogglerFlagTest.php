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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DemoTogglerFlagTest extends KernelTestCase
{
    public function testDemoFlagIsInactiveByDefault(): void
    {
        self::bootKernel();

        $toggle = self::getContainer()->get(ToggleInterface::class);
        self::assertInstanceOf(ToggleInterface::class, $toggle);

        // With SOLIDINVOICE_MODE defaulting to 'self-hosted', the flag must resolve to false.
        self::assertFalse($toggle->isActive('demo_enabled'));
    }
}
