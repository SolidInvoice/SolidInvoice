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

namespace SolidInvoice\UserBundle\Tests\OAuth;

use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\OAuth\OAuthUser;

#[CoversClass(OAuthUser::class)]
final class OAuthUserTest extends TestCase
{
    public function testGetEmailWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'email' => 'test@example.com',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertSame('test@example.com', $oauthUser->getEmail());
    }

    public function testGetEmailWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createStub(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertNull($oauthUser->getEmail());
    }

    public function testGetFirstNameWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'given_name' => 'John',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertSame('John', $oauthUser->getFirstName());
    }

    public function testGetFirstNameWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createStub(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertSame('', $oauthUser->getFirstName());
    }

    public function testGetId(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $resourceOwner->expects($this->once())
            ->method('getId')
            ->willReturn('12345');

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertEquals('12345', $oauthUser->getId());
    }

    public function testGetLastNameWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'family_name' => 'Doe',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertSame('Doe', $oauthUser->getLastName());
    }

    public function testGetLastNameWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createStub(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertSame('', $oauthUser->getLastName());
    }

    public function testGetPropertyMapWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertSame('googleId', $oauthUser->getPropertyMap());
    }

    public function testGetPropertyMapWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createStub(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertSame('', $oauthUser->getPropertyMap());
    }

    public function testGetEmailVerifiedWithGoogleUserVerified(): void
    {
        $googleUser = new GoogleUser([
            'email_verified' => true,
        ]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertTrue($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithGoogleUserNotVerified(): void
    {
        $googleUser = new GoogleUser([
            'email_verified' => false,
        ]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertFalse($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithGoogleUserNoVerificationInfo(): void
    {
        $googleUser = new GoogleUser([]);

        $oauthUser = new OAuthUser($googleUser);

        self::assertFalse($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createStub(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        self::assertFalse($oauthUser->getEmailVerified());
    }
}
