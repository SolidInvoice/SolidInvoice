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

namespace SolidInvoice\UserBundle\Tests\Twig\Components;

use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use SolidInvoice\UserBundle\Twig\Components\ApiTokenHistoryModal;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

final class ApiTokenHistoryModalTest extends TestCase
{
    public function testGetTokenReturnsNullWhenTokenIdIsNull(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = null;

        self::assertNull($component->getToken());
    }

    public function testGetTokenReturnsNullWhenTokenNotFound(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();
        $tokenIdString = (string) $tokenId;

        $tokenRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::callback(static fn (Ulid $ulid) => (string) $ulid === $tokenIdString))
            ->willReturn(null);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = $tokenIdString;

        self::assertNull($component->getToken());
    }

    public function testGetTokenReturnsNullWhenUserDoesNotOwnToken(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();
        $tokenIdString = (string) $tokenId;

        // Create a user that owns the token
        $tokenOwner = $this->createStub(User::class);
        $tokenOwner->method('getId')->willReturn(Ulid::fromString('01HN0000000000000000000001'));

        // Create a different current user
        $currentUser = $this->createStub(User::class);
        $currentUser->method('getId')->willReturn(Ulid::fromString('01HN0000000000000000000002'));

        $apiToken = $this->createMock(ApiToken::class);
        $apiToken->method('getUser')->willReturn($tokenOwner);

        $tokenRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::callback(static fn (Ulid $ulid) => (string) $ulid === $tokenIdString))
            ->willReturn($apiToken);

        $security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = $tokenIdString;

        self::assertNull($component->getToken());
    }

    public function testGetTokenReturnsTokenWhenUserOwnsIt(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();
        $tokenIdString = (string) $tokenId;
        $userId = Ulid::fromString('01HN0000000000000000000001');

        // Create current user
        $currentUser = $this->createStub(User::class);
        $currentUser->method('getId')->willReturn($userId);

        // Create API token owned by current user
        $apiToken = $this->createMock(ApiToken::class);
        $apiToken->method('getUser')->willReturn($currentUser);

        $tokenRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::callback(static fn (Ulid $ulid) => (string) $ulid === $tokenIdString))
            ->willReturn($apiToken);

        $security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = $tokenIdString;

        self::assertSame($apiToken, $component->getToken());
    }

    public function testGetTokenReturnsNullWhenCurrentUserIsNotUserInstance(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();
        $tokenIdString = (string) $tokenId;

        $apiToken = $this->createMock(ApiToken::class);

        $tokenRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::callback(static fn (Ulid $ulid) => (string) $ulid === $tokenIdString))
            ->willReturn($apiToken);

        // Current user is not a User instance
        $security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = $tokenIdString;

        self::assertNull($component->getToken());
    }

    public function testOpenActionSetsTokenId(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = (string) Ulid::generate();

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->open($tokenId);

        self::assertSame($tokenId, $component->tokenId);
    }

    public function testCloseActionClearsTokenId(): void
    {
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $component = new ApiTokenHistoryModal($tokenRepository, $security);
        $component->tokenId = (string) Ulid::generate();
        $component->close();

        self::assertNull($component->tokenId);
    }
}
