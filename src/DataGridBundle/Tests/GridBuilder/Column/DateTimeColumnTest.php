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
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn
 */
final class DateTimeColumnTest extends TestCase
{
    public function testFormat(): void
    {
        $column = DateTimeColumn::new('date');

        self::assertSame('Y-m-d H:i:s', $column->formatValue('Y-m-d H:i:s')->getFormat());
        self::assertSame('d F Y H:i:s', $column->formatValue('d F Y H:i:s')->getFormat());
    }
}
