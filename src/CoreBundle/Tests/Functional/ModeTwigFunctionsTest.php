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

final class ModeTwigFunctionsTest extends KernelTestCase
{
    public function testModeTwigFunctionsResolveToSelfHostedDefaults(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        self::assertSame('self-hosted', $twig->createTemplate('{{ app_mode() }}')->render());
        self::assertSame('no', $twig->createTemplate("{{ is_demo() ? 'yes' : 'no' }}")->render());
        self::assertSame('no', $twig->createTemplate("{{ is_saas() ? 'yes' : 'no' }}")->render());
        self::assertSame('', $twig->createTemplate('{{ demo_username() }}')->render());
        self::assertSame('', $twig->createTemplate('{{ demo_password() }}')->render());
        self::assertSame('', $twig->createTemplate('{{ demo_signup_url() }}')->render());
    }
}
