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

use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;

/**
 * Money is a decimal string in `currency`, at that currency's own scale.
 */
final readonly class InvoiceOutput
{
    /**
     * @param list<LineOutput> $lines
     * @param list<InvoiceTaxOutput> $invoice_taxes
     */
    public function __construct(
        public ?string $id,
        public string $invoice_number,
        public ?InvoiceStatus $status,
        public ?ClientSummaryOutput $client,
        public ?string $currency,
        public string $total,
        public string $base_total,
        public string $balance,
        public string $tax,
        public string $withholding_amount,
        public string $amount_payable,
        public DiscountOutput $discount,
        public string $invoice_date,
        public ?string $due,
        public PaymentTerms $payment_terms,
        public ?string $paid_date,
        public ?string $created,
        public ?string $terms,
        public ?string $notes,
        public array $lines,
        public array $invoice_taxes,
    ) {
    }
}
