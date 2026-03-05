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

use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Exceptions\CommunicationException;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function in_array;
use function sprintf;
use function strlen;
use function substr;

final class MultiSearchService
{
    /**
     * @var array<string, ResultFormatterInterface>|null
     */
    private ?array $formatterMap = null;

    /**
     * @param iterable<ResultFormatterInterface> $formatters
     */
    public function __construct(
        private readonly Client $client,
        private readonly CompanySelector $companySelector,
        private readonly iterable $formatters,
        private readonly string $indexPrefix,
    ) {
    }

    /**
     * @return array<string, list<SearchResult>> keyed by logical index name
     */
    public function search(ParsedQuery $parsedQuery): array
    {
        $companyId = $this->companySelector->getCompany();

        if ($companyId === null) {
            return [];
        }

        $formatterMap = $this->buildFormatterMap();
        $companyFilter = sprintf('companyId = "%s"', $companyId->toBase58());

        // Restrict to requested indices, or default to all
        $indicesToQuery = $parsedQuery->indices !== []
            ? array_filter(
                array_keys($formatterMap),
                static fn (string $k) => in_array($k, $parsedQuery->indices, true),
            )
            : array_keys($formatterMap);

        $queries = [];
        foreach ($indicesToQuery as $indexName) {
            $indexSpecificFilters = $parsedQuery->indexFilters[$indexName] ?? [];
            $allFilters = array_merge([$companyFilter], $indexSpecificFilters);

            if ($allFilters === [$companyFilter] && $parsedQuery->fulltext === '' && $indicesToQuery === array_keys($formatterMap)) {
                continue;
            }

            $q = (new SearchQuery())
                ->setIndexUid($this->indexPrefix . $indexName)
                ->setQuery($parsedQuery->fulltext)
                ->setFilter($allFilters)
                ->setLimit($parsedQuery->hitsPerIndex);

            if ($parsedQuery->sort !== []) {
                $q->setSort($parsedQuery->sort);
            }

            $queries[] = $q;
        }

        if ($queries === []) {
            return [];
        }

        try {
            $multiSearchResult = $this->client->multiSearch($queries);
        } catch (CommunicationException | ApiException) {
            return [];
        }

        $grouped = [];
        foreach ($multiSearchResult['results'] as $result) {
            $logicalIndex = substr($result['indexUid'], strlen($this->indexPrefix));
            $formatter = $formatterMap[$logicalIndex] ?? null;
            $hits = $result['hits'] ?? [];

            if ($formatter === null || $hits === []) {
                continue;
            }

            $grouped[$logicalIndex] = array_map(
                static fn (array $hit) => $formatter->format($hit),
                $hits,
            );
        }

        return $grouped;
    }

    /**
     * @return array<string, ResultFormatterInterface>
     */
    private function buildFormatterMap(): array
    {
        if ($this->formatterMap !== null) {
            return $this->formatterMap;
        }

        $map = [];
        foreach ($this->formatters as $formatter) {
            $map[$formatter->getIndexName()] = $formatter;
        }

        return $this->formatterMap = $map;
    }
}
