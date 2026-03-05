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

final readonly class ParsedQuery
{
    /**
     * @param list<string>                $indices      Empty = search all registered indices
     * @param array<string, list<string>> $indexFilters Per-index Meilisearch filter expressions, keyed by index name
     * @param list<string>                $sort         Meilisearch sort directives e.g. ['total:asc']
     */
    public function __construct(
        public string $fulltext,
        public array $indices = [],
        public array $indexFilters = [],
        public array $sort = [],
        public int $hitsPerIndex = 5,
    ) {
    }
}
