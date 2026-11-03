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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\RelativeDateFormatter;

#[CoversClass(RelativeDateFormatter::class)]
final class RelativeDateFormatterTest extends TestCase
{
    private RelativeDateFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new RelativeDateFormatter();
        // Set a fixed "now" time for consistent testing
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 1, 2, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testFormatReturnsStringForNonRelativeDateColumn(): void
    {
        $column = StringColumn::new('name');

        self::assertSame('test value', $this->formatter->format($column, 'test value'));
    }

    public function testFormatReturnsEmptyStringForNullValue(): void
    {
        $column = RelativeDateColumn::new('created');

        self::assertSame('', $this->formatter->format($column, null));
    }

    public function testFormatReturnsTimeElementForRecentDate(): void
    {
        $column = RelativeDateColumn::new('created')->threshold(7);
        $date = CarbonImmutable::now()->subDays(2);

        $result = $this->formatter->format($column, $date);

        self::assertStringContainsString('<time', (string) $result);
        self::assertStringContainsString('class="datagrid-relative-date"', (string) $result);
        self::assertStringContainsString('datetime="', (string) $result);
        self::assertStringContainsString('title="', (string) $result);
        self::assertStringContainsString('2 days ago', (string) $result);
    }

    public function testFormatReturnsAbsoluteDateBeyondThreshold(): void
    {
        $column = RelativeDateColumn::new('created')->threshold(7);
        $date = CarbonImmutable::now()->subDays(10);

        $result = $this->formatter->format($column, $date);

        self::assertStringContainsString('<time', (string) $result);
        self::assertStringNotContainsString('class="datagrid-relative-date"', (string) $result);
        // Should show absolute date format (Jan 2, 2026 - 10 days = Dec 23, 2025)
        self::assertStringContainsString('Dec 2025', (string) $result);
    }

    public function testFormatUsesCustomThreshold(): void
    {
        $column = RelativeDateColumn::new('created')->threshold(30);
        $date = CarbonImmutable::now()->subDays(15);

        $result = $this->formatter->format($column, $date);

        // 15 days is within 30-day threshold, should show relative
        self::assertStringContainsString('class="datagrid-relative-date"', (string) $result);
        self::assertStringContainsString('2 weeks ago', (string) $result);
    }

    public function testFormatUsesCustomAbsoluteFormat(): void
    {
        $column = RelativeDateColumn::new('created')
            ->threshold(3)
            ->absoluteFormat('Y-m-d');
        $date = CarbonImmutable::now()->subDays(5);

        $result = $this->formatter->format($column, $date);

        // Should show the custom format
        self::assertStringContainsString('2025-12-28', (string) $result);
    }

    public function testFormatHandlesDateTimeInterface(): void
    {
        $column = RelativeDateColumn::new('created');
        $date = CarbonImmutable::parse('2026-01-01 10:00:00');

        $result = $this->formatter->format($column, $date);

        self::assertStringContainsString('<time', (string) $result);
        self::assertStringContainsString('datetime="', (string) $result);
    }

    public function testFormatHandlesStringDate(): void
    {
        $column = RelativeDateColumn::new('created');
        $dateString = '2026-01-01 10:00:00';

        $result = $this->formatter->format($column, $dateString);

        self::assertStringContainsString('<time', (string) $result);
    }

    public function testFormatHandlesInvalidStringDate(): void
    {
        $column = RelativeDateColumn::new('created');

        $result = $this->formatter->format($column, 'not a valid date');

        // Should return the original value as string
        self::assertSame('not a valid date', $result);
    }

    public function testFormatEscapesHtmlInOutput(): void
    {
        $column = RelativeDateColumn::new('created')
            ->absoluteFormat('<script>d M Y</script>');
        $date = CarbonImmutable::now()->subDays(10);

        $result = $this->formatter->format($column, $date);

        self::assertStringNotContainsString('<script>', (string) $result);
    }

    public function testFormatShowsFutureDates(): void
    {
        $column = RelativeDateColumn::new('due');
        $date = CarbonImmutable::now()->addDays(3);

        $result = $this->formatter->format($column, $date);

        self::assertStringContainsString('class="datagrid-relative-date"', (string) $result);
        self::assertStringContainsString('3 days', (string) $result);
    }
}
