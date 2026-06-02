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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Source\ORMSource;

#[CoversClass(ORMSource::class)]
final class ORMSourceTest extends TestCase
{
    private ORMSource $source;

    private ManagerRegistry&Stub $registry;

    private GridInterface&Stub $grid;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->grid = $this->createStub(GridInterface::class);
        $this->source = new ORMSource($this->registry);
    }

    public function testFetchReturnsQueryBuilder(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $repository = $this->createStub(EntityRepository::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $query = new Query($queryBuilder, 'c');

        $this->registry->method('getManagerForClass')->willReturn($em);
        $em->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->grid->method('query')->willReturn($query);

        self::assertSame($query, $this->source->fetch($this->grid));
    }
}
