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

final readonly class ProductOutput
{
    public function __construct(
        public ?string $id,
        public string $name,
        public ?string $description,
        /** Unit price, a decimal string in the invoice or quote currency it is added to. */
        public string $price,
        /** Default quantity, a decimal string. */
        public string $quantity,
        public ?TaxOutput $tax,
        public ?string $created,
        public ?string $updated,
    ) {
    }
}
