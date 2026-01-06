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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory as ApiTokenHistoryEntity;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use SolidInvoice\UserBundle\Twig\Components\ApiTokenHistory;
use Symfony\Component\Uid\Ulid;

final class ApiTokenHistoryTest extends TestCase
{
    public function testGetHistoryReturnsEmptyArrayWhenTokenIsNull(): void
    {
        $repository = $this->createMock(ApiTokenHistoryRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $component = new ApiTokenHistory($repository, $entityManager);
        $component->token = null;

        self::assertSame([], $component->getHistory());
    }

    public function testGetHistoryReturnsEmptyArrayWhenTokenNotFound(): void
    {
        $repository = $this->createMock(ApiTokenHistoryRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $tokenId = Ulid::generate();

        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(ApiToken::class, $tokenId)
            ->willReturn(null);

        $component = new ApiTokenHistory($repository, $entityManager);
        $component->token = (string) $tokenId;

        self::assertSame([], $component->getHistory());
    }

    public function testGetHistoryReturnsHistoryRecordsForToken(): void
    {
        $repository = $this->createMock(ApiTokenHistoryRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $tokenId = Ulid::generate();
        $apiToken = $this->createMock(ApiToken::class);

        $history1 = $this->createMock(ApiTokenHistoryEntity::class);
        $history2 = $this->createMock(ApiTokenHistoryEntity::class);

        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(ApiToken::class, $tokenId)
            ->willReturn($apiToken);

        $repository
            ->expects(self::once())
            ->method('getHistoryForToken')
            ->with($apiToken)
            ->willReturn(new \ArrayIterator([$history1, $history2]));

        $component = new ApiTokenHistory($repository, $entityManager);
        $component->token = (string) $tokenId;

        $result = $component->getHistory();

        self::assertCount(2, $result);
        self::assertSame($history1, $result[0]);
        self::assertSame($history2, $result[1]);
    }
}
