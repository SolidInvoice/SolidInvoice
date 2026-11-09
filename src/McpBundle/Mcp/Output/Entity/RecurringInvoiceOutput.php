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

use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;

/**
 * Money is a decimal string in `currency`, at that currency's own scale.
 */
final readonly class RecurringInvoiceOutput
{
    /**
     * @param list<LineOutput> $lines
     * @param list<InvoiceTaxOutput> $invoice_taxes
     */
    public function __construct(
        public ?string $id,
        public ?RecurringInvoiceStatus $status,
        public ?ClientSummaryOutput $client,
        public ?string $currency,
        public string $total,
        public string $base_total,
        public string $tax,
        public string $withholding_amount,
        public string $amount_payable,
        public ?string $date_start,
        public ?string $date_end,
        public RecurringScheduleOutput $schedule,
        public int $generated_invoice_count,
        public PaymentTerms $payment_terms,
        public ?string $terms,
        public ?string $notes,
        public ?string $created,
        public array $lines,
        public array $invoice_taxes,
    ) {
    }
}
