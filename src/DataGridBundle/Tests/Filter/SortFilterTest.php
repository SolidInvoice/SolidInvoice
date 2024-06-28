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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\Filter\SortFilter;

/**
 * @covers \SolidInvoice\DataGridBundle\Filter\SortFilter
 */
final class SortFilterTest extends TestCase
{
    private SortFilter $filter;

    private QueryBuilder&MockObject $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->filter = new SortFilter('field');
    }

    public function testFilterAppliesCorrectOrderingWhenFieldIsSet(): void
    {
        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('d.field', Criteria::ASC);

        $this->filter->filter($this->queryBuilder, null);
    }

    public function testFilterDoesNotApplyOrderingWhenFieldIsNotSet(): void
    {
        $sortFilter = new SortFilter('');

        $this->queryBuilder
            ->expects($this->never())
            ->method('orderBy');

        $sortFilter->filter($this->queryBuilder, null);
    }

    public function testFilterAppliesCorrectOrderingWhenDirectionIsDesc(): void
    {
        $sortFilter = new SortFilter('field', Criteria::DESC);

        $this->queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('d.field', Criteria::DESC);

        $sortFilter->filter($this->queryBuilder, null);
    }
}
