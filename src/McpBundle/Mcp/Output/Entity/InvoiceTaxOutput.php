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
use SolidInvoice\TaxBundle\Enum\TaxDirection;

final readonly class InvoiceTaxOutput
{
    public function __construct(
        public ?string $id,
        public ?string $tax_id,
        public ?string $name,
        public string $rate,
        public TaxCategory $category,
        public TaxDirection $direction,
        public int $sequence,
        public string $amount,
        public ?string $note,
    ) {
    }
}
