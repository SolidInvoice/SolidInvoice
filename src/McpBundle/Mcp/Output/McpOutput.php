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

use Attribute;

/**
 * Declares the DTO an MCP tool method returns, so its `outputSchema` is generated from that
 * class (see {@see \SolidInvoice\McpBundle\DependencyInjection\McpOutputSchemaPass}).
 *
 * With `list: true` the tool returns a {@see ListOutput} whose `results` are `$class` items.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class McpOutput
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public string $class,
        public bool $list = false,
    ) {
    }
}
