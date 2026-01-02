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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Column;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn
 */
final class RelativeDateColumnTest extends TestCase
{
    private RelativeDateColumn $column;

    protected function setUp(): void
    {
        $this->column = RelativeDateColumn::new('created');
    }

    public function testNewSetsCellClassToColDate(): void
    {
        self::assertSame('col-date', $this->column->getCellClass());
    }

    public function testGetThresholdReturnsSevenByDefault(): void
    {
        self::assertSame(7, $this->column->getThreshold());
    }

    public function testThresholdSetsAndGetsCorrectly(): void
    {
        $result = $this->column->threshold(30);

        self::assertSame($this->column, $result);
        self::assertSame(30, $this->column->getThreshold());
    }

    public function testGetAbsoluteFormatReturnsDefaultFormat(): void
    {
        self::assertSame('d M Y', $this->column->getAbsoluteFormat());
    }

    public function testAbsoluteFormatSetsAndGetsCorrectly(): void
    {
        $result = $this->column->absoluteFormat('Y-m-d H:i');

        self::assertSame($this->column, $result);
        self::assertSame('Y-m-d H:i', $this->column->getAbsoluteFormat());
    }

    public function testFluentInterface(): void
    {
        $result = $this->column
            ->threshold(14)
            ->absoluteFormat('F j, Y')
            ->label('Created At')
            ->sortable(true);

        self::assertSame($this->column, $result);
        self::assertSame(14, $this->column->getThreshold());
        self::assertSame('F j, Y', $this->column->getAbsoluteFormat());
    }

    public function testInheritsFromDateTimeColumn(): void
    {
        // Test that it has inherited properties from DateTimeColumn
        self::assertSame('created', $this->column->getField());
        self::assertTrue($this->column->isSortable());
    }
}
