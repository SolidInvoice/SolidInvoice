<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\StringFormatter;
use stdClass;
use Stringable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function spl_object_hash;
use function strtoupper;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\StringFormatter
 */
final class StringFormatterTest extends TestCase
{
    private StringFormatter $formatter;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader());
        $this->formatter = new StringFormatter($twig);
    }

    public function testFormatReturnsStringForStringColumnWithTwigFunction(): void
    {
        $column = StringColumn::new('column')->twigFunction('max');

        $this->assertSame('20', $this->formatter->format($column, [10, 20]));
    }

    public function testFormatReturnsStringForStringColumnWithoutTwigFunction(): void
    {
        $column = StringColumn::new('column')->format(fn ($value) => strtoupper($value));

        $this->assertSame('VALUE', $this->formatter->format($column, 'value'));
    }

    public function testFormatReturnsStringForStringableObject(): void
    {
        $column = $this->createMock(Column::class);
        $object = new class() {
            public function __toString(): string
            {
                return 'value';
            }
        };

        $this->assertSame('value', $this->formatter->format($column, $object));
    }

    public function testFormatReturnsStringForClassImplementsStringableObject(): void
    {
        $column = $this->createMock(Column::class);
        $object = new class() implements Stringable {
            public function __toString(): string
            {
                return 'value';
            }
        };

        $this->assertSame('value', $this->formatter->format($column, $object));
    }

    public function testFormatReturnsObjectHashForObjectWithoutToString(): void
    {
        $column = $this->createMock(Column::class);
        $object = new stdClass();

        $this->assertSame(spl_object_hash($object), $this->formatter->format($column, $object));
    }

    public function testFormatReturnsStringForNumericValue(): void
    {
        $column = $this->createMock(Column::class);

        $this->assertSame('123', $this->formatter->format($column, 123));
    }

    public function testFormatReturnsStringForBooleanValue(): void
    {
        $column = $this->createMock(Column::class);

        $this->assertSame('true', $this->formatter->format($column, true));
        $this->assertSame('false', $this->formatter->format($column, false));
    }

    public function testFormatReturnsEmptyStringForNullValue(): void
    {
        $column = $this->createMock(Column::class);

        $this->assertSame('', $this->formatter->format($column, null));
    }
}
