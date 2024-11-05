<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\Filter\SearchFilter;

/**
 * @covers \SolidInvoice\DataGridBundle\Filter\SearchFilter
 */
final class SearchFilterTest extends TestCase
{
    public function testFilterAddsCorrectConditionsWhenQueryIsNotEmpty(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('expr')->willReturn(new Expr());
        $queryBuilder->expects($this->once())->method('andWhere')->with((new Expr())->orX('d.field1 LIKE :q', 'd.field2 LIKE :q'));
        $queryBuilder->expects($this->once())->method('setParameter')->with('q', '%query%');

        $filter = new SearchFilter(['field1', 'field2']);
        $filter->filter($queryBuilder, 'query');
    }

    public function testFilterDoesNotAddConditionsWhenQueryIsEmpty(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->expects($this->never())->method('setParameter');

        $filter = new SearchFilter(['field1', 'field2']);
        $filter->filter($queryBuilder, '');
    }

    public function testFilterHandlesFieldsWithAliasCorrectly(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('expr')->willReturn(new Expr());
        $queryBuilder->expects($this->once())->method('andWhere')->with((new Expr())->orX('b.field1 LIKE :q', 'd.field2 LIKE :q'));
        $queryBuilder->expects($this->once())->method('setParameter')->with('q', '%query%');

        $filter = new SearchFilter(['b.field1', 'field2']);
        $filter->filter($queryBuilder, 'query');
    }
}
