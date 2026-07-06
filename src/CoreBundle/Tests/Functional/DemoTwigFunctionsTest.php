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
use Twig\Environment;

final class DemoTwigFunctionsTest extends KernelTestCase
{
    public function testDemoTwigFunctionsResolveToDisabledDefaults(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        self::assertSame('no', $twig->createTemplate("{{ demo_enabled() ? 'yes' : 'no' }}")->render());
        self::assertSame('', $twig->createTemplate('{{ demo_username() }}')->render());
        self::assertSame('', $twig->createTemplate('{{ demo_password() }}')->render());
        self::assertSame('', $twig->createTemplate('{{ demo_signup_url() }}')->render());
    }
}
