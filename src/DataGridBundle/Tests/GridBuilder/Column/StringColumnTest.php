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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use function strtoupper;

#[CoversClass(StringColumn::class)]
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
        self::assertSame('template', $this->column->getTemplate());
        self::assertSame(['param' => 'value'], $this->column->getTemplateParams());
    }

    public function testTemplateParamsWithCallback(): void
    {
        $this->column->template('template', static fn () => ['param' => 'value']);
        self::assertSame('template', $this->column->getTemplate());
        self::assertSame(['param' => 'value'], $this->column->getTemplateParams());
    }

    public function testTwigFunctionSetsAndGetsCorrectly(): void
    {
        $this->column->twigFunction('upper');
        self::assertSame('upper', $this->column->getTwigFunction());
    }

    public function testFormatSetsAndGetsCorrectly(): void
    {
        $callback = static fn ($value) => strtoupper((string) $value);
        $this->column->formatValue($callback);
        self::assertSame($callback, $this->column->getFormatValue());
    }
}
