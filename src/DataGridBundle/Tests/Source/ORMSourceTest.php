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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\Source\ORMSource;

class ORMSourceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFetch()
    {
        $qb = M::mock(QueryBuilder::class);
        $repository = M::mock(EntityManager::class);
        $registry = M::mock(ManagerRegistry::class);

        $registry->shouldReceive('getRepository')
        ->with('a')
        ->andReturn($repository);

        $repository->shouldReceive('b')
        ->andReturn($qb);

        $source = new ORMSource($registry, 'a', 'b');

        $data = $source->fetch();

        self::assertSame($qb, $data);
    }

    public function testFetchException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Grid source should return a query builder');

        $repository = M::mock(EntityManager::class);
        $registry = M::mock(ManagerRegistry::class);

        $registry->shouldReceive('getRepository')
        ->with('a')
        ->andReturn($repository);

        $repository->shouldReceive('b')
        ->andReturn([]);

        $source = new ORMSource($registry, 'a', 'b');

        $data = $source->fetch();
    }
}
