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

namespace SolidInvoice\SaasBundle\Tests\Security\OneTap;

use Google\Client;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Security\OneTap\GoogleIdTokenVerifier;
use SolidInvoice\SaasBundle\Security\OneTap\InvalidIdTokenException;

#[CoversClass(GoogleIdTokenVerifier::class)]
final class GoogleIdTokenVerifierTest extends TestCase
{
    private Client & MockObject $client;

    private GoogleIdTokenVerifier $verifier;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->verifier = new GoogleIdTokenVerifier($this->client);
    }

    public function testReturnsMappedTokenForValidPayload(): void
    {
        $this->client->method('verifyIdToken')->willReturn([
            'sub' => 'google-id-1',
            'email' => 'user@example.com',
            'email_verified' => true,
            'given_name' => 'Ada',
            'family_name' => 'Lovelace',
            'nonce' => 'nonce-abc',
        ]);

        $token = $this->verifier->verify('jwt');

        self::assertSame('google-id-1', $token->identity->googleId);
        self::assertSame('user@example.com', $token->identity->email);
        self::assertTrue($token->identity->emailVerified);
        self::assertSame('Ada', $token->identity->firstName);
        self::assertSame('Lovelace', $token->identity->lastName);
        self::assertSame('nonce-abc', $token->nonce);
    }

    public function testNonceIsNullWhenAbsent(): void
    {
        $this->client->method('verifyIdToken')->willReturn([
            'sub' => 'google-id-1',
            'email' => 'user@example.com',
            'email_verified' => true,
        ]);

        $token = $this->verifier->verify('jwt');

        self::assertNull($token->nonce);
        self::assertNull($token->identity->firstName);
    }

    public function testThrowsWhenVerificationFails(): void
    {
        $this->client->method('verifyIdToken')->willReturn(false);

        $this->expectException(InvalidIdTokenException::class);

        $this->verifier->verify('jwt');
    }

    public function testThrowsWhenEmailNotVerified(): void
    {
        $this->client->method('verifyIdToken')->willReturn([
            'sub' => 'google-id-1',
            'email' => 'user@example.com',
            'email_verified' => false,
        ]);

        $this->expectException(InvalidIdTokenException::class);

        $this->verifier->verify('jwt');
    }

    public function testThrowsWhenRequiredClaimsMissing(): void
    {
        $this->client->method('verifyIdToken')->willReturn([
            'email_verified' => true,
            'email' => 'user@example.com',
        ]);

        $this->expectException(InvalidIdTokenException::class);

        $this->verifier->verify('jwt');
    }

    public function testWrapsUnderlyingClientErrors(): void
    {
        $this->client->method('verifyIdToken')->willThrowException(new LogicException('boom'));

        $this->expectException(InvalidIdTokenException::class);

        $this->verifier->verify('jwt');
    }
}
