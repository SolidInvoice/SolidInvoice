<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Tests\Source;

use CSBill\DataGridBundle\Source\ORMSource;
use Mockery as M;

class ORMSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testFetch()
    {
        $qb = M::mock('Doctrine\ORM\QueryBuilder');
        $repository = M::mock('Doctrine\ORM\EntityManager');
        $registry = M::mock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->shouldReceive('getRepository')
        ->with('a')
        ->andReturn($repository);

        $repository->shouldReceive('b')
        ->andReturn($qb);

        $source = new ORMSource($registry, 'a', 'b');

        $data = $source->fetch();

        $this->assertSame($qb, $data);
    }

    public function testFetchException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Grid source should return a query builder');

        $repository = M::mock('Doctrine\ORM\EntityManager');
        $registry = M::mock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->shouldReceive('getRepository')
        ->with('a')
        ->andReturn($repository);

        $repository->shouldReceive('b')
        ->andReturn([]);

        $source = new ORMSource($registry, 'a', 'b');

        $data = $source->fetch();
    }
}
