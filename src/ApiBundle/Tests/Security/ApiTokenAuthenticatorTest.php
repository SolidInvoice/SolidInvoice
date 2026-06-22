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

namespace SolidInvoice\ApiBundle\Tests\Security;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiTokenAuthenticatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSupportsWithoutHeader(): void
    {
        self::assertFalse($this->authenticator()->supports(new Request()));
    }

    #[DataProvider('emptyTokenProvider')]
    public function testSupportsRejectsEmptyToken(string $token): void
    {
        self::assertFalse($this->authenticator()->supports($this->requestWithToken($token)));
    }

    public function testSupportsAcceptsNonEmptyToken(): void
    {
        self::assertTrue($this->authenticator()->supports($this->requestWithToken('a-valid-token')));
    }

    #[DataProvider('emptyTokenProvider')]
    public function testAuthenticateRejectsEmptyToken(string $token): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided');

        $this->authenticator()->authenticate($this->requestWithToken($token));
    }

    public function testAuthenticateTrimsTokenBeforeLookup(): void
    {
        $userProvider = M::mock(ApiTokenUserProvider::class);
        $userProvider->expects('getUsernameForToken')
            ->with('a-valid-token')
            ->andReturn('user@example.com');

        $passport = $this->authenticator($userProvider)->authenticate($this->requestWithToken('  a-valid-token  '));

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertSame('user@example.com', $passport->getBadge(UserBadge::class)->getUserIdentifier());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyTokenProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace' => ['   '];
    }

    private function requestWithToken(string $token): Request
    {
        $request = new Request();
        $request->headers->set('X-API-TOKEN', $token);

        return $request;
    }

    /**
     * The constructor pulls in several final, infrastructure-bound services that
     * are not exercised by token extraction, so the instance is built without the
     * constructor and only the user provider is injected when required.
     */
    private function authenticator(?ApiTokenUserProvider $userProvider = null): ApiTokenAuthenticator
    {
        $reflection = new ReflectionClass(ApiTokenAuthenticator::class);
        $authenticator = $reflection->newInstanceWithoutConstructor();

        if ($userProvider instanceof ApiTokenUserProvider) {
            $reflection->getProperty('userProvider')->setValue($authenticator, $userProvider);
        }

        return $authenticator;
    }
}
