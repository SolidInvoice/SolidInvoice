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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DemoEnvDefaultsTest extends KernelTestCase
{
    public function testDemoEnvDefaultsAreRegistered(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertSame('0', $container->getParameter('env(SOLIDINVOICE_DEMO)'));
        self::assertSame('', $container->getParameter('env(SOLIDINVOICE_DEMO_USERNAME)'));
        self::assertSame('', $container->getParameter('env(SOLIDINVOICE_DEMO_PASSWORD)'));
        self::assertSame('', $container->getParameter('env(SOLIDINVOICE_DEMO_SIGNUP_URL)'));
    }
}
