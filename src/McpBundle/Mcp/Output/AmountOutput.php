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
 * A money total in one currency: a decimal string at that currency's own scale.
 */
final readonly class AmountOutput
{
    public function __construct(
        public string $currency,
        public string $amount,
    ) {
    }
}
