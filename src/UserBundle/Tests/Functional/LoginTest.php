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

use SolidInvoice\ApiBundle\Test\ApiTestCase;

/**
 * @group functional
 */
final class LoginTest extends ApiTestCase
{
    public function testRedirectToLoginPage(): void
    {
        $client = self::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/');
        self::assertStringContainsString('/login', $crawler->getUri());
    }
}
