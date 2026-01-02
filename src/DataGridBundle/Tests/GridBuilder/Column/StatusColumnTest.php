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
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn
 */
final class StatusColumnTest extends TestCase
{
    private StatusColumn $column;

    protected function setUp(): void
    {
        $this->column = StatusColumn::new('status');
    }

    public function testGetStatusMapReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->column->getStatusMap());
    }

    public function testStatusMapSetsAndGetsCorrectly(): void
    {
        $map = [
            'paid' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
        ];

        $result = $this->column->statusMap($map);

        self::assertSame($this->column, $result);
        self::assertSame($map, $this->column->getStatusMap());
    }

    public function testGetDefaultVariantReturnsSecondaryByDefault(): void
    {
        self::assertSame('secondary', $this->column->getDefaultVariant());
    }

    public function testVariantSetsDefaultVariant(): void
    {
        $result = $this->column->variant('info');

        self::assertSame($this->column, $result);
        self::assertSame('info', $this->column->getDefaultVariant());
    }

    public function testIsTitleCaseReturnsTrueByDefault(): void
    {
        self::assertTrue($this->column->isTitleCase());
    }

    public function testTitleCaseCanBeDisabled(): void
    {
        $result = $this->column->titleCase(false);

        self::assertSame($this->column, $result);
        self::assertFalse($this->column->isTitleCase());
    }

    public function testTitleCaseCanBeReEnabled(): void
    {
        $this->column->titleCase(false);
        $this->column->titleCase(true);

        self::assertTrue($this->column->isTitleCase());
    }

    public function testGetVariantForStatusReturnsMapValue(): void
    {
        $this->column->statusMap([
            'paid' => 'success',
            'pending' => 'warning',
        ]);

        self::assertSame('success', $this->column->getVariantForStatus('paid'));
        self::assertSame('warning', $this->column->getVariantForStatus('pending'));
    }

    public function testGetVariantForStatusReturnsDefaultWhenNotInMap(): void
    {
        $this->column->statusMap([
            'paid' => 'success',
        ]);

        self::assertSame('secondary', $this->column->getVariantForStatus('unknown'));
    }

    public function testGetVariantForStatusReturnsCustomDefaultWhenNotInMap(): void
    {
        $this->column
            ->statusMap(['paid' => 'success'])
            ->variant('info');

        self::assertSame('info', $this->column->getVariantForStatus('unknown'));
    }

    public function testGetVariantForStatusIsCaseInsensitive(): void
    {
        $this->column->statusMap([
            'paid' => 'success',
        ]);

        self::assertSame('success', $this->column->getVariantForStatus('PAID'));
        self::assertSame('success', $this->column->getVariantForStatus('Paid'));
        self::assertSame('success', $this->column->getVariantForStatus('paid'));
    }

    public function testFluentInterface(): void
    {
        $result = $this->column
            ->statusMap(['active' => 'success'])
            ->variant('primary')
            ->titleCase(false)
            ->label('Status')
            ->sortable(false);

        self::assertSame($this->column, $result);
    }

    public function testInheritsFromColumn(): void
    {
        $this->column->label('Status Column');
        $label = $this->column->getLabel();
        self::assertInstanceOf(TranslatableMessage::class, $label);
        self::assertSame('Status Column', $label->getMessage());

        $this->column->sortable(false);
        self::assertFalse($this->column->isSortable());

        self::assertSame('status', $this->column->getField());
    }
}
