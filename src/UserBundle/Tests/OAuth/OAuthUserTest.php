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
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\OAuth\OAuthUser;

/** @covers \SolidInvoice\UserBundle\OAuth\OAuthUser */
final class OAuthUserTest extends TestCase
{
    public function testGetEmailWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'email' => 'test@example.com',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertEquals('test@example.com', $oauthUser->getEmail());
    }

    public function testGetEmailWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertNull($oauthUser->getEmail());
    }

    public function testGetFirstNameWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'given_name' => 'John',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertEquals('John', $oauthUser->getFirstName());
    }

    public function testGetFirstNameWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertEquals('', $oauthUser->getFirstName());
    }

    public function testGetId(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $resourceOwner->expects($this->once())
            ->method('getId')
            ->willReturn('12345');

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertEquals('12345', $oauthUser->getId());
    }

    public function testGetLastNameWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([
            'family_name' => 'Doe',
        ]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertEquals('Doe', $oauthUser->getLastName());
    }

    public function testGetLastNameWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertEquals('', $oauthUser->getLastName());
    }

    public function testGetPropertyMapWithGoogleUser(): void
    {
        $googleUser = new GoogleUser([]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertEquals('googleId', $oauthUser->getPropertyMap());
    }

    public function testGetPropertyMapWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertEquals('', $oauthUser->getPropertyMap());
    }

    public function testGetEmailVerifiedWithGoogleUserVerified(): void
    {
        $googleUser = new GoogleUser([
            'email_verified' => true,
        ]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertTrue($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithGoogleUserNotVerified(): void
    {
        $googleUser = new GoogleUser([
            'email_verified' => false,
        ]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertFalse($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithGoogleUserNoVerificationInfo(): void
    {
        $googleUser = new GoogleUser([]);

        $oauthUser = new OAuthUser($googleUser);

        $this->assertFalse($oauthUser->getEmailVerified());
    }

    public function testGetEmailVerifiedWithNonGoogleUser(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $oauthUser = new OAuthUser($resourceOwner);

        $this->assertFalse($oauthUser->getEmailVerified());
    }
}
