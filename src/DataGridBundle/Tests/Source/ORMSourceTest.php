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

namespace SolidInvoice\DataGridBundle\Tests\Source;

use AssertionError;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Source\ORMSource;

/**
 * @covers \SolidInvoice\DataGridBundle\Source\ORMSource
 */
final class ORMSourceTest extends TestCase
{
    private ORMSource $source;

    private ManagerRegistry&MockObject $registry;

    private GridInterface&MockObject $grid;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->grid = $this->createMock(GridInterface::class);
        $this->source = new ORMSource($this->registry);
    }

    public function testFetchReturnsQueryBuilder(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->registry->method('getManagerForClass')->willReturn($em);
        $em->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->assertSame($queryBuilder, $this->source->fetch($this->grid));
    }

    public function testFetchThrowsExceptionWhenManagerIsNotEntityManager(): void
    {
        $this->registry->method('getManagerForClass')->willReturn(null);

        $this->expectException(AssertionError::class);

        $this->source->fetch($this->grid);
    }
}
