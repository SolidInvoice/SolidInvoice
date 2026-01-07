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
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use SolidInvoice\UserBundle\Twig\Components\ApiTokenHistory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

final class ApiTokenHistoryTest extends TestCase
{
    public function testGetHistoryReturnsEmptyArrayWhenTokenIsNull(): void
    {
        $historyRepository = $this->createMock(ApiTokenHistoryRepository::class);
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $component = new ApiTokenHistory($historyRepository, $tokenRepository, $security);
        $component->token = null;

        self::assertSame([], $component->getHistory());
    }

    public function testGetHistoryReturnsEmptyArrayWhenTokenNotFound(): void
    {
        $historyRepository = $this->createMock(ApiTokenHistoryRepository::class);
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();

        $tokenRepository
            ->expects(self::once())
            ->method('find')
            ->with($tokenId)
            ->willReturn(null);

        $component = new ApiTokenHistory($historyRepository, $tokenRepository, $security);
        $component->token = (string) $tokenId;

        self::assertSame([], $component->getHistory());
    }

    public function testGetHistoryReturnsEmptyArrayWhenUserDoesNotOwnToken(): void
    {
        $historyRepository = $this->createMock(ApiTokenHistoryRepository::class);
        $tokenRepository = $this->createMock(ApiTokenRepository::class);
        $security = $this->createMock(Security::class);

        $tokenId = Ulid::generate();

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
            ->with($tokenId)
            ->willReturn($apiToken);

        $security
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($currentUser);

        // History repository should NOT be called due to authorization check
        $historyRepository
            ->expects(self::never())
            ->method('getHistoryForToken');

        $component = new ApiTokenHistory($historyRepository, $tokenRepository, $security);
        $component->token = (string) $tokenId;

        $result = $component->getHistory();

        self::assertSame([], $result);
    }
}
