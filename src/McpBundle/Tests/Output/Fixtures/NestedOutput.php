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

namespace SolidInvoice\McpBundle\Tests\Output\Fixtures;

use SolidInvoice\McpBundle\Tests\Output\Fixtures\LineOutput as Line;

final readonly class NestedOutput
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public ?Line $first,
        /** @var list<Line> */
        public array $lines,
        public array $tags,
    ) {
    }
}
