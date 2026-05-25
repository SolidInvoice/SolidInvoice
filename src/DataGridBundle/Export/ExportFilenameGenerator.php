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

namespace SolidInvoice\DataGridBundle\Export;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use function array_filter;
use function array_map;
use function implode;
use function is_array;
use function preg_replace;
use function strtolower;
use function substr;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\Export\ExportFilenameGeneratorTest
 */
final class ExportFilenameGenerator
{
    private const int MAX_FILTER_SUMMARY_LENGTH = 60;

    /**
     * @param array<string, mixed> $gridFilters
     */
    public function generate(
        string $gridName,
        ExportFormat $format,
        array $gridFilters = [],
        ?DateTimeImmutable $date = null,
    ): string {
        $date ??= CarbonImmutable::now();

        $summary = $this->buildFilterSummary($gridFilters);

        $filename = sprintf('%s-%s', $gridName, $date->format('Y-m-d'));
        if ($summary !== '') {
            $filename .= '.' . $summary;
        }

        return $filename . '.' . $format->extension();
    }

    /**
     * @param array<string, mixed> $gridFilters
     */
    private function buildFilterSummary(array $gridFilters): string
    {
        if ($gridFilters === []) {
            return '';
        }

        $parts = [];

        foreach ($gridFilters as $key => $value) {
            $safeKey = $this->sanitizeFilterKey((string) $key);

            if ($safeKey === '') {
                continue;
            }

            $parts[] = $safeKey . '-' . $this->sanitizeFilterValue($value);
        }

        $summary = implode('.', array_filter($parts, static fn (string $part): bool => $part !== ''));

        return substr($summary, 0, self::MAX_FILTER_SUMMARY_LENGTH);
    }

    private function sanitizeFilterKey(string $key): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower($key));
    }

    private function sanitizeFilterValue(mixed $value): string
    {
        if (is_array($value)) {
            if (isset($value['start']) || isset($value['end'])) {
                $range = array_filter([
                    isset($value['start']) ? (string) $value['start'] : null,
                    isset($value['end']) ? (string) $value['end'] : null,
                ]);
                return (string) preg_replace('/[^a-z0-9_]/', '', strtolower(implode('_', $range)));
            }

            return implode('_', array_filter(array_map(
                fn (mixed $v): string => $this->sanitizeFilterKey((string) $v),
                $value,
            )));
        }

        return $this->sanitizeFilterKey((string) $value);
    }
}
