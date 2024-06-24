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
use SolidInvoice\DataGridBundle\GridBuilder\Column\UrlColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\UrlFormatter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\UrlFormatter
 */
final class UrlFormatterTest extends TestCase
{
    private UrlFormatter $formatter;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader());
        $this->formatter = new UrlFormatter($twig);
    }

    public function testFormatReturnsCorrectUrlForStringValue(): void
    {
        $column = UrlColumn::new('url');

        $this->assertSame('<a href="https://example.com" target="_blank">https://example.com</a>', $this->formatter->format($column, 'https://example.com'));
    }

    public function testFormatReturnsEmptyUrlForNullValue(): void
    {
        $column = UrlColumn::new('url');

        $this->assertSame('<a href="" target="_blank"></a>', $this->formatter->format($column, null));
    }
}
