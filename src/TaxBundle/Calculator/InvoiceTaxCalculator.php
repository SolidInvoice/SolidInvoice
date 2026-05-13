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

namespace SolidInvoice\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\Result\InvoiceLevelBreakdown;

/**
 * Computes whole-document (invoice-level) tax breakdowns.
 *
 * Skeleton in US-005; the actual math (e.g. shipping tax, document-wide fees) lands in
 * US-008. Returning an empty breakdown keeps the orchestrator and {@see TotalCalculator}
 * delegation in place without changing existing totals.
 */
final class InvoiceTaxCalculator
{
    public function calculateInvoiceLevel(
        BaseInvoice|Quote $document,
        BigDecimal $subTotal,
        BigDecimal $totalLineTax,
        Rounder $rounder,
    ): InvoiceLevelBreakdown {
        return InvoiceLevelBreakdown::empty();
    }
}
