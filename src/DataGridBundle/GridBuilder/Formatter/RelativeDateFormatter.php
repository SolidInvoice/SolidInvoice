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

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use function htmlspecialchars;
use function sprintf;

/**
 * Formats dates as relative time (e.g., "3 days ago", "in 2 hours").
 *
 * Output:
 *   <time class="datagrid-relative-date" datetime="2026-01-01T10:00:00Z" title="01 Jan 2026">3 days ago</time>
 */
final class RelativeDateFormatter implements FormatterInterface
{
    public function format(Column $column, mixed $value): string
    {
        if (! $column instanceof RelativeDateColumn) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        // Convert to DateTimeInterface if needed
        if (! $value instanceof DateTimeInterface) {
            try {
                $value = new CarbonImmutable((string) $value);
            } catch (\Exception) {
                return (string) $value;
            }
        }

        $value = CarbonImmutable::instance($value);

        $now = CarbonImmutable::now();
        $diff = $value->diff($now);

        $absoluteDate = $value->format($column->getAbsoluteFormat());
        $isoDate = $value->format(DateTimeInterface::ATOM);

        // If beyond threshold, show absolute date
        if ($diff->days > $column->getThreshold()) {
            return sprintf(
                '<time datetime="%s" title="%s">%s</time>',
                htmlspecialchars($isoDate, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($absoluteDate, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($absoluteDate, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<time class="datagrid-relative-date" datetime="%s" title="%s">%s</time>',
            htmlspecialchars($isoDate, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($absoluteDate, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($diff->forHumans(syntax: CarbonInterface::DIFF_RELATIVE_TO_NOW, parts: 1), ENT_QUOTES, 'UTF-8')
        );
    }
}
