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

namespace SolidInvoice\UserBundle\Tests\Functional;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class LoginTest extends WebTestCase
{
    use Factories;
    use EnsureApplicationInstalled;

    public function testRedirectToLoginPage(): void
    {
        UserFactory::createOne(['companies' => [$this->company]]);

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/');
        self::assertStringContainsString('/login', $crawler->getUri());
    }
}
