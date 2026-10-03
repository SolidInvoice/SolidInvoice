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

namespace SolidInvoice\UserBundle\Tests\OAuth\Provider;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SolidInvoice\UserBundle\OAuth\Provider\OidcProvider;
use function json_encode;

#[CoversClass(OidcProvider::class)]
final class OidcProviderTest extends TestCase
{
    public function testDiscoversEndpointsFromIssuer(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://id.example/.well-known/openid-configuration',
                $this->anything(),
            )
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'authorization_endpoint' => 'https://id.example/authorize',
                'token_endpoint' => 'https://id.example/token',
                'userinfo_endpoint' => 'https://id.example/userinfo',
            ], JSON_THROW_ON_ERROR)));

        $provider = new OidcProvider([
            'issuer' => 'https://id.example',
            'clientId' => 'client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://app.example/oauth/check/oidc',
        ], ['httpClient' => $httpClient]);

        self::assertSame('https://id.example/authorize', $provider->getBaseAuthorizationUrl());
        self::assertSame('https://id.example/token', $provider->getBaseAccessTokenUrl([]));
        self::assertSame('https://id.example/userinfo', $provider->getResourceOwnerDetailsUrl(new AccessToken(['access_token' => 'token'])));
    }

    public function testExplicitEndpointsSkipDiscovery(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $provider = new OidcProvider([
            'urlAuthorize' => 'https://id.example/authorize',
            'urlAccessToken' => 'https://id.example/token',
            'urlResourceOwnerDetails' => 'https://id.example/userinfo',
            'clientId' => 'client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://app.example/oauth/check/oidc',
        ], ['httpClient' => $httpClient]);

        self::assertSame('https://id.example/authorize', $provider->getBaseAuthorizationUrl());
    }

    public function testIssuerTrailingSlashIsNormalised(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://id.example/.well-known/openid-configuration', $this->anything())
            ->willReturn(new Response(200, [], json_encode([
                'authorization_endpoint' => 'https://id.example/authorize',
                'token_endpoint' => 'https://id.example/token',
                'userinfo_endpoint' => 'https://id.example/userinfo',
            ], JSON_THROW_ON_ERROR)));

        $provider = new OidcProvider([
            'issuer' => 'https://id.example/',
            'clientId' => 'client',
            'clientSecret' => 'secret',
        ], ['httpClient' => $httpClient]);

        self::assertSame('https://id.example/token', $provider->getBaseAccessTokenUrl([]));
    }

    public function testIssuerIsRequiredWhenEndpointsAreMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "issuer" option is required');

        new OidcProvider(['clientId' => 'client', 'clientSecret' => 'secret']);
    }

    public function testResourceOwnerIsIdentifiedBySubClaim(): void
    {
        $provider = new OidcProvider([
            'urlAuthorize' => 'https://id.example/authorize',
            'urlAccessToken' => 'https://id.example/token',
            'urlResourceOwnerDetails' => 'https://id.example/userinfo',
            'clientId' => 'client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://app.example/oauth/check/oidc',
        ]);

        $createResourceOwner = new ReflectionMethod($provider, 'createResourceOwner');

        $resourceOwner = $createResourceOwner->invoke(
            $provider,
            ['sub' => 'abc-123', 'email' => 'user@example.com'],
            new AccessToken(['access_token' => 'token']),
        );

        self::assertSame('abc-123', $resourceOwner->getId());
    }
}
