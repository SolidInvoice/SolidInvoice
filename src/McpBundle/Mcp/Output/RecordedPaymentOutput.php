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

use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentOutput;

final readonly class RecordedPaymentOutput
{
    /**
     * @param string $credit_added the overpayment added to the client's credit, in the payment's currency
     */
    public function __construct(
        public PaymentOutput $payment,
        public string $credit_added,
    ) {
    }
}
