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

namespace SolidInvoice\McpBundle\Mcp\Output;

use function array_values;

/**
 * The envelope every MCP list tool returns. Its schema comes from
 * {@see OutputSchemaGenerator::generateList()}, which must stay in step with these properties.
 */
final readonly class ListOutput
{
    /**
     * @var list<object>
     */
    public array $results;

    /**
     * @param array<object> $results re-indexed, so a gappy array (array_filter() output) still encodes as a JSON array
     */
    public function __construct(
        array $results,
        public int $page,
        public int $items_per_page,
        public int $total,
    ) {
        $this->results = array_values($results);
    }
}
