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
use SolidInvoice\CoreBundle\Company\CompanySelector;

final class MultiSearchService
{
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
     * Search across all registered indices simultaneously, filtered to the current company.
     *
     * @return array<string, list<SearchResult>> keyed by index name
     */
    public function search(string $query, int $hitsPerIndex = 5): array
    {
        $companyId = $this->companySelector->getCompany();

        if ($companyId === null) {
            return [];
        }

        $formatterMap = $this->buildFormatterMap();
        $companyFilter = sprintf('companyId = "%s"', $companyId->toBase58());

        $queries = [];
        foreach (array_keys($formatterMap) as $indexName) {
            $queries[] = (new SearchQuery())
                ->setIndexUid($this->indexPrefix . $indexName)
                ->setQuery($query)
                ->setFilter([$companyFilter])
                ->setLimit($hitsPerIndex);
        }

        $multiSearchResult = $this->client->multiSearch($queries);

        $grouped = [];
        foreach ($multiSearchResult['results'] as $result) {
            $logicalIndex = substr($result['indexUid'], strlen($this->indexPrefix));
            $formatter = $formatterMap[$logicalIndex] ?? null;

            if ($formatter === null || $result['hits'] === []) {
                continue;
            }

            $results = [];
            foreach ($result['hits'] as $hit) {
                $results[] = $formatter->format($hit);
            }

            $grouped[$logicalIndex] = $results;
        }

        return $grouped;
    }

    /**
     * @return array<string, ResultFormatterInterface>
     */
    private function buildFormatterMap(): array
    {
        $map = [];
        foreach ($this->formatters as $formatter) {
            $map[$formatter->getIndexName()] = $formatter;
        }

        return $map;
    }
}
