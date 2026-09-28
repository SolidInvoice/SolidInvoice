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
 * One invoice line (BG-25).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\InvoiceLineTest
 */
final readonly class InvoiceLine
{
    public function __construct(
        /**
         * BT-126. The line's identifier, 1-based.
         */
        public string $identifier,
        /**
         * BT-129. The invoiced quantity.
         */
        public BigDecimal $invoicedQuantity,
        /**
         * BT-131. The line's net amount, with any inclusive tax already extracted.
         */
        public BigDecimal $netAmount,
        /**
         * BG-29 (BT-146…BT-150 via PriceDetails). The line's price details.
         */
        public PriceDetails $price,
        /**
         * BG-30 (BT-151/BT-152 via LineVatInformation). The line's VAT information.
         */
        public LineVatInformation $vat,
        /**
         * BG-31 (BT-153…BT-161 via ItemInformation). Information about the item being invoiced.
         */
        public ItemInformation $item,
        /**
         * BT-127. A free-text note on the line.
         */
        public ?string $note = null,
        /**
         * BT-128/128-1. An identifier for the object this line applies to, e.g. a meter.
         */
        public ?Identifier $objectIdentifier = null,
        /**
         * BT-130. The invoiced quantity's unit of measure code.
         */
        public ?string $unitOfMeasureCode = null,
        /**
         * BT-132. A reference to the corresponding line on the buyer's purchase order.
         */
        public ?string $orderLineReference = null,
        /**
         * BT-133. The buyer's accounting reference for this line.
         */
        public ?string $buyerAccountingReference = null,
        /**
         * BG-26 (BT-134/BT-135 via InvoicingPeriod). The invoicing period this line's charge covers.
         */
        public ?InvoicingPeriod $invoicingPeriod = null,
        /**
         * BG-27 (BT-136…BT-140 via Allowance). Line-level allowances.
         *
         * @var list<Allowance>
         */
        public array $allowances = [],
        /**
         * BG-28 (BT-141…BT-145 via Charge). Line-level charges.
         *
         * @var list<Charge>
         */
        public array $charges = [],
    ) {
    }
}
