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

namespace SolidInvoice\UserBundle\Tests\Action\Security;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\UserBundle\Action\Security\Login;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class LoginTest extends TestCase
{
    private function makeRequest(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function csrf(): CsrfTokenManagerInterface
    {
        $manager = $this->createStub(CsrfTokenManagerInterface::class);
        $manager->method('getToken')->willReturn(new CsrfToken('authenticate', 'token'));

        return $manager;
    }

    public function testSeedsDemoUsernameWhenDemoModeAndNoLastUsername(): void
    {
        $modeResolver = new ModeResolver('demo', 'demo@example.com', 'demo-password');

        $result = (new Login($modeResolver))($this->makeRequest(), $this->csrf());

        self::assertSame('demo@example.com', $result['last_username']);
    }

    public function testDoesNotSeedUsernameWhenSelfHosted(): void
    {
        $modeResolver = new ModeResolver();

        $result = (new Login($modeResolver))($this->makeRequest(), $this->csrf());

        self::assertSame('', (string) $result['last_username']);
    }

    public function testDoesNotOverwriteExistingLastUsernameWhenDemoModeEnabled(): void
    {
        $modeResolver = new ModeResolver('demo', 'demo@example.com', 'demo-password');

        $request = $this->makeRequest();
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, 'someone@else.com');

        $result = (new Login($modeResolver))($request, $this->csrf());

        self::assertSame('someone@else.com', $result['last_username']);
    }
}
