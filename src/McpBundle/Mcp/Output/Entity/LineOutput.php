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

/**
 * `price` and `total` are decimal strings in the document's currency; `qty` is the plain quantity.
 */
final readonly class LineOutput
{
    /**
     * @param list<LineTaxOutput> $taxes
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $price,
        public string $qty,
        public string $total,
        public array $taxes,
    ) {
    }
}
