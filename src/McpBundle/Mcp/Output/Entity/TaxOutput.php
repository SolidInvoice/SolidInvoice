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

namespace SolidInvoice\McpBundle\Mcp\Output\Entity;

use SolidInvoice\TaxBundle\Enum\TaxCategory;

final readonly class TaxOutput
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?float $rate,
        public ?string $type,
        public TaxCategory $category,
        public bool $compound,
    ) {
    }
}
