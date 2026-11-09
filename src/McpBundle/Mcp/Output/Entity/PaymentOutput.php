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

use SolidInvoice\PaymentBundle\Enum\PaymentStatus;

/**
 * Money is a decimal string in `currency`, at that currency's own scale.
 */
final readonly class PaymentOutput
{
    public function __construct(
        public ?string $id,
        public ?PaymentStatus $status,
        public ?string $method,
        public string $amount,
        public string $currency,
        public ?string $invoice_id,
        public ?string $client_id,
        public ?string $created,
        public ?string $completed,
    ) {
    }
}
