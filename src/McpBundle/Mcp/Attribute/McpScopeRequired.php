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

namespace SolidInvoice\McpBundle\Mcp\Attribute;

use SolidInvoice\McpBundle\Security\McpScope;

/**
 * Declares the scope a tool method requires. Read on the method, used as
 * documentation and discoverable by callers; actual enforcement happens via
 * {@see \SolidInvoice\McpBundle\Mcp\McpScopeGuard}.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final class McpScopeRequired
{
    public function __construct(
        public readonly McpScope $scope
    ) {
    }
}
