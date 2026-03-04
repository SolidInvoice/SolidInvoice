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

interface ResultFormatterInterface
{
    /**
     * The Meilisearch index name this formatter handles (without prefix).
     */
    public function getIndexName(): string;

    /**
     * Transform a raw Meilisearch hit into a typed SearchResult DTO.
     *
     * @param array<string, mixed> $hit
     */
    public function format(array $hit): SearchResult;
}
