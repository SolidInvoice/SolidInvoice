<?php

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
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use function strtoupper;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn
 */
final class StringColumnTest extends TestCase
{
    private StringColumn $column;

    protected function setUp(): void
    {
        $this->column = StringColumn::new('field');
    }

    public function testTemplateSetsAndGetsCorrectly(): void
    {
        $this->column->template('template', ['param' => 'value']);
        $this->assertSame('template', $this->column->getTemplate());
        $this->assertSame(['param' => 'value'], $this->column->getTemplateParams());
    }

    public function testTemplateParamsWithCallback(): void
    {
        $this->column->template('template', static fn () => ['param' => 'value']);
        $this->assertSame('template', $this->column->getTemplate());
        $this->assertSame(['param' => 'value'], $this->column->getTemplateParams());
    }

    public function testTwigFunctionSetsAndGetsCorrectly(): void
    {
        $this->column->twigFunction('upper');
        $this->assertSame('upper', $this->column->getTwigFunction());
    }

    public function testFormatSetsAndGetsCorrectly(): void
    {
        $callback = static fn ($value) => strtoupper($value);
        $this->column->formatValue($callback);
        $this->assertSame($callback, $this->column->getCallback());
    }

    public function testDefaultCallbackReturnsValueUnchanged(): void
    {
        $this->assertSame('value', $this->column->getCallback()('value'));
    }
}
