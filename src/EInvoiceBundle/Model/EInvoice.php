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

use DateTimeImmutable;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;

/**
 * The root of the EN 16931 semantic model: one electronic invoice (BG-1…BG-3 at this level; every
 * other business group is carried by a nested class).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\EInvoiceTest
 */
final readonly class EInvoice
{
    public function __construct(
        /**
         * BT-1. The invoice number.
         */
        public string $invoiceNumber,
        /**
         * BT-2. The invoice issue date.
         */
        public DateTimeImmutable $issueDate,
        /**
         * BT-3. The invoice type code, e.g. commercial invoice or credit note.
         */
        public InvoiceTypeCode $invoiceTypeCode,
        /**
         * BT-5. The invoice currency code, ISO 4217 alpha-3.
         */
        public string $currencyCode,
        /**
         * BG-4 (BT-27… via Seller). The seller.
         */
        public Seller $seller,
        /**
         * BG-7 (BT-44… via Buyer). The buyer.
         */
        public Buyer $buyer,
        /**
         * BG-22 (BT-106… via DocumentTotals). The document totals.
         */
        public DocumentTotals $documentTotals,
        /**
         * BT-6. The VAT accounting currency code, where it differs from BT-5.
         */
        public ?string $vatAccountingCurrencyCode = null,
        /**
         * BT-7. The tax point date, where it differs from BT-2.
         */
        public ?DateTimeImmutable $taxPointDate = null,
        /**
         * BT-8. The code for the tax point date, where BT-7 is not given directly.
         */
        public ?string $vatPointDateCode = null,
        /**
         * BT-9. The payment due date.
         */
        public ?DateTimeImmutable $dueDate = null,
        /**
         * BT-10. The buyer reference, e.g. a Leitweg-ID.
         */
        public ?string $buyerReference = null,
        /**
         * BT-11. A reference to the project this invoice belongs to.
         */
        public ?string $projectReference = null,
        /**
         * BT-12. A reference to the contract this invoice belongs to.
         */
        public ?string $contractReference = null,
        /**
         * BT-13. A reference to the buyer's purchase order.
         */
        public ?string $purchaseOrderReference = null,
        /**
         * BT-14. A reference to the seller's sales order.
         */
        public ?string $salesOrderReference = null,
        /**
         * BT-15. A reference to the receiving advice.
         */
        public ?string $receivingAdviceReference = null,
        /**
         * BT-16. A reference to the despatch advice.
         */
        public ?string $despatchAdviceReference = null,
        /**
         * BT-17. A reference to the tender or lot this invoice relates to.
         */
        public ?string $tenderOrLotReference = null,
        /**
         * BT-18/18-1. An identifier for the object the invoice applies to, e.g. a meter.
         */
        public ?Identifier $invoicedObjectIdentifier = null,
        /**
         * BT-19. The buyer's accounting reference for this invoice.
         */
        public ?string $buyerAccountingReference = null,
        /**
         * BT-20. The payment terms, as free text.
         */
        public ?string $paymentTerms = null,
        /**
         * BT-23. The business process type this invoice belongs to.
         */
        public ?string $businessProcessType = null,
        /**
         * BT-24. The identifier of the specification this invoice conforms to.
         */
        public ?string $specificationIdentifier = null,
        /**
         * BG-10 (BT-59…BT-61 via Payee). The payee, where it differs from the seller.
         */
        public ?Payee $payee = null,
        /**
         * BG-11 (BT-62/BT-63 via SellerTaxRepresentative). The seller's tax representative.
         */
        public ?SellerTaxRepresentative $sellerTaxRepresentative = null,
        /**
         * BG-13 (BT-70…BT-72 via DeliveryInformation). Delivery information.
         */
        public ?DeliveryInformation $deliveryInformation = null,
        /**
         * BG-16 (BT-81…BT-83 via PaymentInstructions). Payment instructions.
         */
        public ?PaymentInstructions $paymentInstructions = null,
        /**
         * BG-1 (BT-21/BT-22 via InvoiceNote). Free-text notes on the invoice.
         *
         * @var list<InvoiceNote>
         */
        public array $notes = [],
        /**
         * BG-3 (BT-25/BT-26 via PrecedingInvoiceReference). References to preceding invoices.
         *
         * @var list<PrecedingInvoiceReference>
         */
        public array $precedingInvoiceReferences = [],
        /**
         * BG-20 (BT-92…BT-98 via Allowance). Document-level allowances.
         *
         * @var list<Allowance>
         */
        public array $allowances = [],
        /**
         * BG-21 (BT-99…BT-105 via Charge). Document-level charges.
         *
         * @var list<Charge>
         */
        public array $charges = [],
        /**
         * BG-23 (BT-116…BT-121 via VatBreakdown). The VAT breakdown, one entry per category/rate.
         *
         * @var list<VatBreakdown>
         */
        public array $vatBreakdowns = [],
        /**
         * BG-24 (BT-122…BT-125 via AdditionalSupportingDocument). Documents supporting the invoice.
         *
         * @var list<AdditionalSupportingDocument>
         */
        public array $additionalSupportingDocuments = [],
        /**
         * BG-25 (BT-126… via InvoiceLine). The invoice lines.
         *
         * @var list<InvoiceLine>
         */
        public array $invoiceLines = [],
    ) {
    }
}
