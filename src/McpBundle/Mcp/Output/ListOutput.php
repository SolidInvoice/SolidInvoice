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

/**
 * The envelope every MCP list tool returns. Its schema comes from
 * {@see OutputSchemaGenerator::generateList()}, which must stay in step with these properties.
 */
final readonly class ListOutput
{
    /**
     * @param list<object> $results
     */
    public function __construct(
        public array $results,
        public int $page,
        public int $items_per_page,
        public int $total,
    ) {
    }
}
