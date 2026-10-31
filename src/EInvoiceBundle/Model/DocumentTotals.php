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

namespace SolidInvoice\EInvoiceBundle\Model;

use Brick\Math\BigDecimal;

/**
 * The document totals (BG-22).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\DocumentTotalsTest
 */
final readonly class DocumentTotals
{
    public function __construct(
        /**
         * BT-106. The sum of all invoice line net amounts.
         */
        public BigDecimal $sumOfLineNetAmounts,
        /**
         * BT-109. The total invoice amount, excluding VAT.
         */
        public BigDecimal $totalWithoutVat,
        /**
         * BT-110. The total VAT amount for the invoice.
         */
        public BigDecimal $vatAmount,
        /**
         * BT-112. The total invoice amount, including VAT.
         */
        public BigDecimal $totalWithVat,
        /**
         * BT-107. The sum of all document-level allowances.
         */
        public ?BigDecimal $sumOfAllowances = null,
        /**
         * BT-108. The sum of all document-level charges.
         */
        public ?BigDecimal $sumOfCharges = null,
        /**
         * BT-111. BT-110 expressed in the VAT accounting currency (BT-6).
         */
        public ?BigDecimal $vatAmountInAccountingCurrency = null,
        /**
         * BT-113. The amount already paid, deducted from the amount due.
         */
        public ?BigDecimal $paidAmount = null,
        /**
         * BT-114. A rounding amount applied to reach BT-115.
         */
        public ?BigDecimal $roundingAmount = null,
        /**
         * BT-115. The amount due for payment.
         */
        public ?BigDecimal $amountDueForPayment = null,
    ) {
    }
}
