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
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final class ApiTokenHistoryGridTest extends TestCase
{
    public function testEntityFQCNReturnsApiTokenHistoryClass(): void
    {
        $grid = new ApiTokenHistoryGrid();

        self::assertSame(ApiTokenHistory::class, $grid->entityFQCN());
    }

    public function testQueryAddsTokenFilterWhenTokenIdProvided(): void
    {
        $tokenId = Ulid::generate();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $query = new Query($queryBuilder, ORMSource::ALIAS);

        $queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with('IDENTITY(' . ORMSource::ALIAS . '.token) = :token')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('token', $tokenId, UlidType::NAME)
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

        $grid = new ApiTokenHistoryGrid();
        $grid->initialize(['token_id' => $tokenId]);

        $result = $grid->query($entityManager, $query);

        self::assertSame($query, $result);
    }

    public function testQueryDoesNotAddTokenFilterWhenTokenIdNotProvided(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $query = new Query($queryBuilder, ORMSource::ALIAS);

        $queryBuilder
            ->expects(self::never())
            ->method('andWhere');

        $queryBuilder
            ->expects(self::never())
            ->method('setParameter');

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

        $grid = new ApiTokenHistoryGrid();

        $result = $grid->query($entityManager, $query);

        self::assertSame($query, $result);
    }
}
