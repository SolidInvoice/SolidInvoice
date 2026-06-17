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

namespace SolidInvoice\UserBundle\Tests\Security\Turnstile;

use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Security\Turnstile\TurnstileVerifier;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TurnstileVerifierTest extends TestCase
{
    public function testVerifyReturnsTrueWhenFeatureDisabledWithoutHttpCall(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            self::fail('No HTTP request should be made when the feature is disabled');
        });

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(false), 'secret');

        self::assertTrue($verifier->verify('token', '127.0.0.1'));
    }

    public function testVerifyReturnsTrueOnSuccessfulResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['success' => true])));

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(true), 'secret');

        self::assertTrue($verifier->verify('token', '127.0.0.1'));
    }

    public function testVerifyReturnsFalseOnUnsuccessfulResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['success' => false])));

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(true), 'secret');

        self::assertFalse($verifier->verify('token', '127.0.0.1'));
    }

    public function testVerifyReturnsFalseForEmptyTokenWithoutHttpCall(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            self::fail('No HTTP request should be made for an empty token');
        });

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(true), 'secret');

        self::assertFalse($verifier->verify('', '127.0.0.1'));
        self::assertFalse($verifier->verify(null, '127.0.0.1'));
    }

    public function testVerifyReturnsFalseWhenSecretMissing(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            self::fail('No HTTP request should be made without a secret key');
        });

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(true), null);

        self::assertFalse($verifier->verify('token', '127.0.0.1'));
    }

    public function testVerifyFailsClosedOnTransportError(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            throw new TransportException('Connection refused');
        });

        $verifier = new TurnstileVerifier($httpClient, $this->toggle(true), 'secret');

        self::assertFalse($verifier->verify('token', '127.0.0.1'));
    }

    private function toggle(bool $active): ToggleInterface
    {
        $toggle = $this->createMock(ToggleInterface::class);
        $toggle->method('isActive')
            ->willReturn($active);

        return $toggle;
    }
}
