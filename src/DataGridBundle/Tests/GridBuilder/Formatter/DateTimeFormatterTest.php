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

use DateTime;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\DateTimeFormatter;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\DateTimeFormatter
 */
final class DateTimeFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new DateTimeFormatter();

        self::assertSame('2021-01-12 12:13:14', $formatter->format(DateTimeColumn::new('date'), new DateTime('2021-01-12 12:13:14')));
        self::assertSame('2021-01-01 00:00:00', $formatter->format(DateTimeColumn::new('date'), '2021-01-01 00:00:00'));

        self::assertSame('12 January 2021 12:13:14', $formatter->format(DateTimeColumn::new('date')->formatValue('d F Y H:i:s'), new DateTime('2021-01-12 12:13:14')));
        self::assertSame('01 January 2021 00:00:00', $formatter->format(DateTimeColumn::new('date')->formatValue('d F Y H:i:s'), '2021-01-01 00:00:00'));
    }
}
