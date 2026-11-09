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
 * What a delete tool returns: the id of the record that is now gone.
 */
final readonly class DeletedOutput
{
    public function __construct(
        public bool $deleted,
        public string $id,
    ) {
    }
}
