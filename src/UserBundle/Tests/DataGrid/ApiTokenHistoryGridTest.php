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

namespace SolidInvoice\UserBundle\Tests\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\UserBundle\DataGrid\ApiTokenHistoryGrid;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

final class ApiTokenHistoryGridTest extends TestCase
{
    public function testEntityFQCNReturnsApiTokenHistoryClass(): void
    {
        $grid = new ApiTokenHistoryGrid($this->createStub(Security::class));

        self::assertSame(ApiTokenHistory::class, $grid->entityFQCN());
    }

    public function testQueryAlwaysScopesToCurrentUser(): void
    {
        $userId = new Ulid();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $query = new Query($queryBuilder, ORMSource::ALIAS);

        $queryBuilder
            ->expects(self::once())
            ->method('join')
            ->with(ORMSource::ALIAS . '.token', 'apiToken')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('apiToken.user = :user')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('user', $userId, UlidType::NAME)
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('orderBy')
            ->with(ORMSource::ALIAS . '.created', 'DESC')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('setMaxResults')
            ->with(100)
            ->willReturnSelf();

        $grid = new ApiTokenHistoryGrid($this->mockSecurity($userId));

        $result = $grid->query($entityManager, $query);

        self::assertSame($query, $result);
    }

    public function testQueryAddsTokenFilterWhenTokenIdProvided(): void
    {
        $userId = new Ulid();
        $tokenId = new Ulid();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $query = new Query($queryBuilder, ORMSource::ALIAS);

        $queryBuilder
            ->expects(self::once())
            ->method('join')
            ->with(ORMSource::ALIAS . '.token', 'apiToken')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(static function (string $where) use ($queryBuilder): QueryBuilder {
                static $calls = [];
                $calls[] = $where;

                self::assertContains($where, [
                    'apiToken.user = :user',
                    'IDENTITY(' . ORMSource::ALIAS . '.token) = :token',
                ]);

                return $queryBuilder;
            });

        $queryBuilder
            ->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(static function (string $name, $value, $type) use ($queryBuilder, $userId, $tokenId): QueryBuilder {
                if ('user' === $name) {
                    self::assertSame($userId, $value);
                    self::assertSame(UlidType::NAME, $type);
                } else {
                    self::assertSame('token', $name);
                    self::assertSame($tokenId, $value);
                    self::assertSame(UlidType::NAME, $type);
                }

                return $queryBuilder;
            });

        $queryBuilder
            ->expects(self::once())
            ->method('orderBy')
            ->with(ORMSource::ALIAS . '.created', 'DESC')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('setMaxResults')
            ->with(100)
            ->willReturnSelf();

        $grid = new ApiTokenHistoryGrid($this->mockSecurity($userId));
        $grid->initialize(['token_id' => $tokenId]);

        $result = $grid->query($entityManager, $query);

        self::assertSame($query, $result);
    }

    private function mockSecurity(Ulid $userId): Security
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }
}
