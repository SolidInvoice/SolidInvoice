<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Tests\Functional;

use Symfony\Component\Panther\PantherTestCase;

/**
 * @group functional
 */
class LoginTest extends PantherTestCase
{
    public function testRedirectToLoginPage()
    {
        $client = self::createPantherClient();
        $client->request('GET', '/');
        $this->assertStringContainsString('/login', $client->getCurrentURL());
    }
}
