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

namespace SolidInvoice\DataGridBundle\Tests\Export;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\DataGridBundle\Export\ExportFilenameGenerator;

#[CoversClass(ExportFilenameGenerator::class)]
final class ExportFilenameGeneratorTest extends TestCase
{
    private ExportFilenameGenerator $generator;

    private DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->generator = new ExportFilenameGenerator();
        $this->date = CarbonImmutable::parse('2026-04-24');
    }

    public function testGeneratesBasicFilenameWithoutFilters(): void
    {
        $filename = $this->generator->generate('invoice_grid', ExportFormat::Csv, [], $this->date);

        self::assertSame('invoice_grid-2026-04-24.csv', $filename);
    }

    public function testAppendsSingleFilterSummary(): void
    {
        $filename = $this->generator->generate(
            'invoice_grid',
            ExportFormat::Json,
            ['status' => 'paid'],
            $this->date,
        );

        self::assertSame('invoice_grid-2026-04-24.status-paid.json', $filename);
    }

    public function testAppendsMultiSelectFilterSummary(): void
    {
        $filename = $this->generator->generate(
            'invoice_grid',
            ExportFormat::Xml,
            ['status' => ['paid', 'draft']],
            $this->date,
        );

        self::assertSame('invoice_grid-2026-04-24.status-paid_draft.xml', $filename);
    }

    public function testHandlesDateRangeFilter(): void
    {
        $filename = $this->generator->generate(
            'invoice_grid',
            ExportFormat::Csv,
            ['invoiceDate' => ['start' => '2026-01-01', 'end' => '2026-03-31']],
            $this->date,
        );

        self::assertSame('invoice_grid-2026-04-24.invoicedate-20260101_20260331.csv', $filename);
    }

    public function testTruncatesLongFilterSummaries(): void
    {
        $filename = $this->generator->generate(
            'invoice_grid',
            ExportFormat::Csv,
            ['status' => str_repeat('a', 200)],
            $this->date,
        );

        $summarySection = explode('.', $filename)[1];
        self::assertLessThanOrEqual(60, strlen($summarySection));
    }

    public function testUsesCurrentDateWhenNotSupplied(): void
    {
        $filename = $this->generator->generate('grid', ExportFormat::Csv);

        self::assertMatchesRegularExpression('/^grid-\d{4}-\d{2}-\d{2}\.csv$/', $filename);
    }
}
