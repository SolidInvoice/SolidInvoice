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

namespace SolidInvoice\CoreBundle\Search;

use DateTimeImmutable;
use DateTimeZone;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

final class SearchQueryParser
{
    /**
     * Sort qualifier values => Meilisearch sort directive
     */
    private const array SORT_MAP = [
        'amount' => 'total:asc',
        'amount_desc' => 'total:desc',
        'date' => 'created:asc',
        'date_desc' => 'created:desc',
    ];

    /**
     * Known index names. The `in:` qualifier is validated against this list.
     */
    private const array KNOWN_INDICES = [
        'invoices',
        'recurring_invoices',
        'quotes',
        'payments',
        'clients',
        'contacts',
    ];

    /**
     * @var array<string, array<string, string>> indexName => [qualifier => meilisearchAttr]
     */
    private array $qualifierMap = [];

    /**
     * @param iterable<ResultFormatterInterface> $formatters
     */
    public function __construct(iterable $formatters)
    {
        foreach ($formatters as $formatter) {
            if ($formatter instanceof QualifiedResultFormatterInterface) {
                $this->qualifierMap[$formatter->getIndexName()] = $formatter->getSupportedQualifiers();
            }
        }
    }

    public function parse(string $raw): ParsedQuery
    {
        $tokens = $this->tokenise($raw);

        $indices = [];
        $filters = [];
        $sort = [];
        $textParts = [];

        foreach ($tokens as $token) {
            if (! str_contains($token, ':')) {
                $textParts[] = $token;
                continue;
            }

            [$qualifier, $value] = explode(':', $token, 2);

            match ($qualifier) {
                'in' => $indices = $this->parseIndices($value),
                'sort' => $sort = $this->parseSort($value),
                default => $this->isKnownQualifier($qualifier)
                    ? ($filters[$qualifier] = $value)
                    : ($textParts[] = $token),
            };
        }

        // Determine which indices to build filters for
        $targetIndices = $indices !== [] ? $indices : array_keys($this->qualifierMap);

        // Build per-index filter expressions — each index only gets filters it supports
        $indexFilters = [];
        foreach ($targetIndices as $indexName) {
            $expressions = $this->buildFilterExpressions($filters, [$indexName]);
            $indexFilters[$indexName] = $expressions;
        }

        return new ParsedQuery(
            fulltext: trim(implode(' ', $textParts)),
            indices: $indices,
            indexFilters: $indexFilters,
            sort: $sort,
        );
    }

    /**
     * Tokenise respecting quoted strings: `client:"Acme Corp"` stays as one token.
     *
     * @return list<string>
     */
    private function tokenise(string $raw): array
    {
        preg_match_all('/\w+:"[^"]*"|\S+/', trim($raw), $matches);

        return $matches[0];
    }

    /**
     * @return list<string>
     */
    private function parseIndices(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $idx) => in_array($idx, self::KNOWN_INDICES, true),
        ));
    }

    /**
     * @return list<string>
     */
    private function parseSort(string $value): array
    {
        return isset(self::SORT_MAP[$value]) ? [self::SORT_MAP[$value]] : [];
    }

    private function isKnownQualifier(string $qualifier): bool
    {
        foreach ($this->qualifierMap as $qualifiers) {
            if (isset($qualifiers[$qualifier])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $qualifiers  qualifier => raw value
     * @param list<string>          $indices      restricted index names (empty = all)
     * @return list<string>
     */
    private function buildFilterExpressions(array $qualifiers, array $indices): array
    {
        if ($qualifiers === []) {
            return [];
        }

        // Collect the set of indices we are building filters for
        $targetIndices = $indices !== [] ? $indices : array_keys($this->qualifierMap);

        // For each qualifier, find the Meilisearch attribute that any target index supports
        $expressions = [];
        foreach ($qualifiers as $qualifier => $value) {
            $attribute = $this->resolveAttribute($qualifier, $targetIndices);

            if ($attribute === null) {
                continue; // qualifier not supported by the scoped indices
            }

            $expressions[] = $this->buildExpression($attribute, $value);
        }

        return array_values(array_filter($expressions));
    }

    /**
     * Returns the Meilisearch attribute name for a qualifier if any targeted index supports it.
     *
     * @param list<string> $targetIndices
     */
    private function resolveAttribute(string $qualifier, array $targetIndices): ?string
    {
        foreach ($targetIndices as $index) {
            $attr = $this->qualifierMap[$index][$qualifier] ?? null;

            if ($attr !== null) {
                return $attr;
            }
        }

        return null;
    }

    private function buildExpression(string $attribute, string $value): string
    {
        // Range: 100..500 or date..date
        if (str_contains($value, '..')) {
            [$from, $to] = explode('..', $value, 2);

            if ($attribute === 'created') {
                if ($from === '' || $to === '') {
                    return '';
                }

                try {
                    $fromTs = $this->parseDate($from);
                    $toTs = $this->parseDate($to, endOfDay: true);
                } catch (\Throwable) {
                    return '';
                }

                return sprintf(
                    '%s >= %d AND %s <= %d',
                    $attribute,
                    $fromTs,
                    $attribute,
                    $toTs,
                );
            }

            return sprintf('%s >= %s AND %s <= %s', $attribute, $from, $attribute, $to);
        }

        // Comparison operators: >=, <=, >, < (check longer first)
        foreach (['>=', '<=', '>', '<'] as $op) {
            if (str_starts_with($value, $op)) {
                $operand = substr($value, strlen($op));

                if ($attribute === 'created') {
                    try {
                        $ts = $this->parseDate($operand);
                    } catch (\Throwable) {
                        return '';
                    }

                    return sprintf('%s %s %d', $attribute, $op, $ts);
                }

                if (! is_numeric($operand)) {
                    return '';
                }

                return sprintf('%s %s %s', $attribute, $op, $operand);
            }
        }

        // Multi-value: paid,pending
        if (str_contains($value, ',')) {
            $values = array_map(
                static fn (string $v) => '"' . trim($v, '"') . '"',
                explode(',', $value),
            );

            return sprintf('%s IN [%s]', $attribute, implode(', ', $values));
        }

        // Equality (strip surrounding quotes if present, re-add for Meilisearch syntax)
        $clean = trim($value, '"');

        // Numeric equality: no quotes needed
        if (is_numeric($clean)) {
            return sprintf('%s = %s', $attribute, $clean);
        }

        return sprintf('%s = "%s"', $attribute, $clean);
    }

    private function parseDate(string $date, bool $endOfDay = false): int
    {
        $dt = new DateTimeImmutable($date, new DateTimeZone('UTC'));

        if ($endOfDay) {
            $dt = $dt->setTime(23, 59, 59);
        }

        return $dt->getTimestamp();
    }
}
