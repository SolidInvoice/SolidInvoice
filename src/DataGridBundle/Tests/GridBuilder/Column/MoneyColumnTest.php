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
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn
 */
final class MoneyColumnTest extends TestCase
{
    private MoneyColumn $column;

    protected function setUp(): void
    {
        $this->column = MoneyColumn::new('amount');
    }

    public function testNewSetsCellClassToColMoney(): void
    {
        self::assertSame('col-money', $this->column->getCellClass());
    }

    public function testGetFieldReturnsConstructorValue(): void
    {
        self::assertSame('amount', $this->column->getField());
    }

    public function testInheritsFromColumn(): void
    {
        // Test inherited functionality
        self::assertTrue($this->column->isSortable());
        self::assertTrue($this->column->isSearchable());

        $this->column->label('Total Amount');
        $label = $this->column->getLabel();
        self::assertInstanceOf(TranslatableMessage::class, $label);
        self::assertSame('Total Amount', $label->getMessage());
    }

    public function testFluentInterface(): void
    {
        $result = $this->column
            ->label('Amount')
            ->sortable(true)
            ->searchable(false);

        self::assertSame($this->column, $result);
    }
}
