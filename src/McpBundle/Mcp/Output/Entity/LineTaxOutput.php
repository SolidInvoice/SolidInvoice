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
use SolidInvoice\TaxBundle\Enum\TaxType;

final readonly class LineTaxOutput
{
    public function __construct(
        public ?string $id,
        public ?string $tax_id,
        public ?string $name,
        public string $rate,
        public TaxType $type,
        public TaxCategory $category,
        public bool $compound,
        public int $sequence,
        public string $amount,
    ) {
    }
}
