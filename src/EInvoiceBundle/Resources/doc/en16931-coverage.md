# EN 16931 business term coverage

Scope: **EN 16931-1:2017+A1** only — BT-1…BT-165, BG-1…BG-32. The 2026 edition adds
BT-166…BT-179; they are not modelled here because SolidWorx does not have the 2026 term list, and
guessing a business term is worse than omitting one (design §11.1, §13.3). EN 16931's own
numbering is not fully sequential (for example there is no BT-4); a gap in the table below with no
row is a gap in the standard's numbering, not an omission by SolidInvoice.

Each row names the `Model/` class and property a term maps to, or the reason it is `null` today
and the issue that closes it. "Not sourced; no issue named" marks a gap this review found that the
SOL-84 design did not call out — flagged on SOL-84 rather than guessed.

## BG-1 — Invoice note / header

| BT | Term | Status |
|---|---|---|
| BT-1 | Invoice number | `EInvoice::$invoiceNumber` ← `Invoice::getInvoiceId()` |
| BT-2 | Issue date | `EInvoice::$issueDate` ← `Invoice::getInvoiceDate()` |
| BT-3 | Invoice type code | `EInvoice::$invoiceTypeCode` ← `Invoice::getInvoiceTypeCode()`. SOL-82 landed before SOL-211, so this reads the real column rather than the design's original hard-coded `InvoiceTypeCode::CommercialInvoice` — a disclosed deviation from SOL-211. |
| BT-5 | Invoice currency code | `EInvoice::$currencyCode` ← `Client::getCurrencyCode()`, falling back to `SystemConfig`'s currency (design §5.2) |
| BT-6 | VAT accounting currency code | Null — no VAT accounting currency concept. Not scheduled. |
| BT-7 | Tax point date | Null — no tax point date. #2665 / SOL-88. |
| BT-8 | VAT point date code | Null — same. #2665 / SOL-88. |
| BT-9 | Payment due date | `EInvoice::$dueDate` ← `Invoice::getDue()` |
| BT-10 | Buyer reference | Null — no buyer reference / Leitweg-ID. #2714 / SOL-78. |
| BT-11 | Project reference | Null — #2665 / SOL-88. |
| BT-12 | Contract reference | Null — #2665 / SOL-88. |
| BT-13 | Purchase order reference | Null — #2665 / SOL-88. |
| BT-14 | Sales order reference | Null — #2665 / SOL-88. |
| BT-15 | Receiving advice reference | Null — #2665 / SOL-88. |
| BT-16 | Despatch advice reference | Null — #2665 / SOL-88. |
| BT-17 | Tender or lot reference | Null — #2665 / SOL-88. |
| BT-18/18-1 | Invoiced object identifier | Null — #2665 / SOL-88. |
| BT-19 | Buyer accounting reference | Null — #2665 / SOL-88, #2714 / SOL-78. |
| BT-20 | Payment terms | `EInvoice::$paymentTerms` ← `BaseInvoice::getTerms()` |
| BT-21 | Invoice note subject code | Null — `InvoiceNote::$subjectCode` has no source; never set. Not sourced; no issue named. |
| BT-22 | Invoice note | `InvoiceNote::$note` ← `BaseInvoice::getNotes()` — one `InvoiceNote` when notes are non-empty, `[]` otherwise |
| BT-23 | Business process type | Null — a profile property (`ProfileInterface::getCustomizationId()`), not an invoice one. #2656 §6 / SOL-103, SOL-104. |
| BT-24 | Specification identifier | Null — same reasoning as BT-23. SOL-103, SOL-104. Also the single-sourced edition claim under design §13.2. |

## BG-3 — Preceding invoice reference

| BT | Term | Status |
|---|---|---|
| BT-25 | Preceding invoice reference | Null — no preceding-invoice reference on the entity. #2657 / SOL-69 (credit notes). |
| BT-26 | Preceding invoice issue date | Null — same. #2657 / SOL-69. |

## BG-4/BG-5/BG-6 — Seller

| BT | Term | Status |
|---|---|---|
| BT-27 | Seller name | `Seller::$name` ← `SystemConfig::get('system/company/company_name')` |
| BT-28 | Seller trading name | Null — no trading name. #2703 / SOL-71. |
| BT-29/29-1 | Seller identifier(s) | Null (`[]`) — #2703 / SOL-71, #2660 / SOL-86. |
| BT-30/30-1 | Seller legal registration identifier | Null — #2703 / SOL-71, #2660 / SOL-86. |
| BT-31 | Seller VAT identifier | `Seller::$vatIdentifier` ← `SystemConfig::get('system/company/vat_number')` |
| BT-32 | Seller tax registration identifier | Null — untyped until #2660 / SOL-86 decides how a user signals BT-31 vs. BT-32. |
| BT-33 | Seller additional legal information | Null — #2703 / SOL-71. |
| BT-34/34-1 | Seller electronic address | Null — #2661 / SOL-90. |
| BT-35…BT-40, BT-162 | Seller postal address (BG-5) | `Seller::$address` ← the `system/company/contact_details/address` JSON setting, decoded defensively; `null` when the setting is absent, empty, malformed, or the wrong shape — never a partial guess. BT-162 (`addressLine3`) is **unverified against the published EN 16931-1:2017/A1 text** (design §4.1.2); nothing in this repository names the term, it is read off the business-group ordering rule. |
| BT-41 | Seller contact name | Null — no setting for a contact name. Not sourced; no issue named. |
| BT-42 | Seller contact telephone | `Contact::$telephone` ← `system/company/contact_details/phone_number` |
| BT-43 | Seller contact email | `Contact::$email` ← `system/company/contact_details/email` |

A seller `Contact` with neither telephone nor email is `null`, not an empty object (design §4.1.6).

## BG-7/BG-8/BG-9 — Buyer

| BT | Term | Status |
|---|---|---|
| BT-44 | Buyer name | `Buyer::$name` ← `Client::getName()` |
| BT-45 | Buyer trading name | Null — #2703 / SOL-71. |
| BT-46/46-1 | Buyer identifier | Null — not sourced today. **Not named in design §5.4 or §6; flagged on SOL-84.** |
| BT-47/47-1 | Buyer legal registration identifier | Null — not sourced today. **Not named in design §5.4 or §6; flagged on SOL-84.** |
| BT-48 | Buyer VAT identifier | `Buyer::$vatIdentifier` ← the first (by id) of `Client::getTaxIdentifiers()`. Proven company-isolated in `Tests/Functional/InvoiceMapperTest::testMappingNeverReadsAnotherCompanysTaxIdentifier()`. |
| BT-49/49-1 | Buyer electronic address | Null — #2661 / SOL-90. |
| BT-50…BT-55, BT-163 | Buyer postal address (BG-8) | `Buyer::$address` ← the first (by id) of `Client::getAddresses()`; `null` if none or empty. BT-163 is unverified against the A1 text, same caveat as BT-162. |
| BT-56 | Buyer contact name | `Contact::$name` ← the first (by id) invoice `Contact`'s first/last name, trimmed |
| BT-57 | Buyer contact telephone | Null — `ClientBundle\Entity\Contact` has no telephone field. |
| BT-58 | Buyer contact email | `Contact::$email` ← the same `Contact`'s email |

## BG-10/BG-11/BG-12 — Payee, seller tax representative

| BT | Term | Status |
|---|---|---|
| BT-59…BT-61 | Payee | Null — no payee concept. #2665 / SOL-88. |
| BT-62/BT-63 | Seller tax representative | Null — #2665 / SOL-88. |
| BT-64…BT-69, BT-164 | Seller tax representative address (BG-12) | Null — same, #2665 / SOL-88. |

## BG-13/BG-14/BG-15 — Delivery

| BT | Term | Status |
|---|---|---|
| BT-70…BT-72 | Delivery information | Null — #2665 / SOL-88. |
| BT-73/BT-74 | Invoicing period (header) | Null — #2665 / SOL-88. |
| BT-75…BT-80, BT-165 | Deliver-to address (BG-15) | Null — #2665 / SOL-88. |

## BG-16…BG-19 — Payment instructions

| BT | Term | Status |
|---|---|---|
| BT-81…BT-91 | Payment instructions, credit transfer, card, direct debit | Null — #2710 / SOL-74, #2662 / SOL-87. |

## BG-20 — Document-level allowance

| BT | Term | Status |
|---|---|---|
| BT-92 | Allowance amount | `Allowance::$amount` ← `Calculator::calculateDiscount($invoice)`, only when `Invoice::getDiscount()->getValue()` is truthy (the same check `TotalCalculator::updateTotal()` uses) |
| BT-93 | Allowance base amount | Null — not named in design §5.4's sourcing table, so not derived. **Not sourced; no issue named; flagged on SOL-84.** |
| BT-94 | Allowance percentage | `Allowance::$percentage` ← `Discount::getValuePercentage()`, only when the discount type is `TYPE_PERCENTAGE`; null for a money-type discount |
| BT-95 | Allowance VAT category | Null — the `Discount` embeddable carries no VAT category. #2663 / SOL-91. |
| BT-96 | Allowance VAT rate | Null — same. #2663 / SOL-91. |
| BT-97 | Allowance reason | Null — `Discount` carries no reason field. **Not sourced; no issue named; flagged on SOL-84.** |
| BT-98 | Allowance reason code | Null — same. **Not sourced; no issue named; flagged on SOL-84.** |

## BG-21 — Document-level charge

| BT | Term | Status |
|---|---|---|
| BT-99…BT-105 | Document-level charge | Null — charges do not exist on the entity model. #2711 / SOL-75. |

## BG-22 — Document totals

| BT | Term | Status |
|---|---|---|
| BT-106 | Sum of line net amounts | `DocumentTotals::$sumOfLineNetAmounts` ← `Invoice::getBaseTotal()` |
| BT-107 | Sum of allowances | `DocumentTotals::$sumOfAllowances` ← the sum of the BG-20 allowance(s) just mapped; `null` when there are none |
| BT-108 | Sum of charges | Null — charges do not exist. #2711 / SOL-75. |
| BT-109 | Total without VAT | `DocumentTotals::$totalWithoutVat` ← `Invoice::getTotal()` minus `Invoice::getTax()` |
| BT-110 | VAT amount | `DocumentTotals::$vatAmount` ← `Invoice::getTax()` |
| BT-111 | VAT amount in accounting currency | Null — no VAT accounting currency. Not scheduled. |
| BT-112 | Total with VAT | `DocumentTotals::$totalWithVat` ← `Invoice::getTotal()` |
| BT-113 | Paid amount | `DocumentTotals::$paidAmount` ← `Invoice::getTotal()` minus `Invoice::getBalance()`, exact subtraction |
| BT-114 | Rounding amount | Null — not stored. Not scheduled. |
| BT-115 | Amount due for payment | `DocumentTotals::$amountDueForPayment` ← `Invoice::getBalance()` |

BT-106…BT-115 are read from the invoice's own persisted `total`/`tax`/`baseTotal`/`balance`
fields, not a fresh `TaxCalculatorInterface::calculate()` call: `CalculationResult` does not carry
the document-level discount (`TotalCalculator` applies that separately), so deriving BT-112 from
it directly would disagree with the amount the invoice actually shows whenever a discount is set.
Disclosed deviation from design §5.3/§5.4, carried over from SOL-211 and confirmed correct here —
see the `InvoiceMapper::mapDocumentTotals()` docblock.

## BG-23 — VAT breakdown

| BT | Term | Status |
|---|---|---|
| BT-116 | Taxable amount | Null, for **every** row, not only a compound or document-level one. `TaxBundle\Calculator\Result\TaxSummaryRow` never carries a taxable base, for any row — design §5.5 only names the compound and document-level cases. Recomputing a base from `amount`/`rate` would be the rate arithmetic design §13.1 forbids. **Flagged on SOL-84** — #2712 / SOL-76 was scoped to the compound case; the plain case has no issue reference at all today. |
| BT-117 | VAT amount (per row) | `VatBreakdown::$taxAmount` ← `TaxSummaryRow::$amount` |
| BT-118 | VAT category code | `VatBreakdown::$categoryCode` ← `VatCategoryCode::fromTaxCategory(TaxSummaryRow::$category)` — `Standard`→`S`, `ZeroRated`→`Z`, `Exempt`→`E`, `OutOfScope`→`O`, `ReverseCharge`→`AE` |
| BT-119 | VAT rate | `VatBreakdown::$rate` ← `TaxSummaryRow::$rate` |
| BT-120 | VAT exemption reason text | Null — #2664 / SOL-95. |
| BT-121 | VAT exemption reason code | Null — #2664 / SOL-95. |

## BG-24 — Additional supporting documents

| BT | Term | Status |
|---|---|---|
| BT-122…BT-125 | Additional supporting document | Null (`[]`) — #2715 / SOL-79. |

## BG-25…BG-32 — Invoice line

| BT | Term | Status |
|---|---|---|
| BT-126 | Line identifier | `InvoiceLine::$identifier` ← `Line::getPosition() + 1` (EN 16931 is 1-based, `LinePosition` is 0-based) |
| BT-127 | Line note | Null — `Line` has no note field distinct from `description` (used for BT-154). **Not sourced; no issue named; flagged on SOL-84.** |
| BT-128/128-1 | Line object identifier | Null — #2665 / SOL-88. |
| BT-129 | Invoiced quantity | `InvoiceLine::$invoicedQuantity` ← `Line::getQty()`, exact (`DECIMAL(20,6)`) |
| BT-130 | Unit of measure code | Null — #2707 / SOL-73. |
| BT-131 | Line net amount | `InvoiceLine::$netAmount` ← `LineBreakdown::$lineSubtotal`, from the single delegated `TaxCalculatorInterface::calculate()` call — the inclusive-extracted subtotal for an inclusive tax, the gross for an exclusive one |
| BT-132 | Order line reference | Null — #2665 / SOL-88. |
| BT-133 | Line buyer accounting reference | Null — #2665 / SOL-88, #2714 / SOL-78. |
| BT-134/BT-135 | Line invoicing period (BG-26) | Null — not sourced; shares BT-73/74's gap. #2665 / SOL-88. |
| BT-136…BT-140 | Line allowance (BG-27) | Null (`[]`) — line-level allowances do not exist. #2711 / SOL-75. |
| BT-141…BT-145 | Line charge (BG-28) | Null (`[]`) — same. #2711 / SOL-75. |
| BT-146 | Item net price | `PriceDetails::$netPrice` ← `Line::getPrice()` |
| BT-147 | Price discount | Null — no line-level discount. #2711 / SOL-75. |
| BT-148 | Item gross price | Null — same. #2711 / SOL-75. |
| BT-149 | Price base quantity | Null — #2707 / SOL-73. |
| BT-150 | Price base quantity unit code | Null — #2707 / SOL-73. |
| BT-151 | Line VAT category code | `LineVatInformation::$categoryCode` ← the first (by sequence) of `Line::getTaxes()`. **A line with no tax at all cannot be mapped — `InvoiceLineMapper` throws rather than fabricate a category, the same rule `InvoiceMapper::map()` applies to a missing client. Flagged on SOL-84: design §5.4 names only the "several taxes" case, not the "zero taxes" one, and SolidInvoice allows an untaxed line.** |
| BT-152 | Line VAT rate | `LineVatInformation::$rate`, same source and caveat as BT-151 |
| BT-153 | Item name | `ItemInformation::$name` ← `Line::getName()` |
| BT-154 | Item description | `ItemInformation::$description` ← `Line::getDescription()` |
| BT-155 | Seller item identifier | Null — #2659 / SOL-85. |
| BT-156 | Buyer item identifier | Null — #2659 / SOL-85. |
| BT-157/157-1 | Item standard identifier | Null — #2659 / SOL-85. |
| BT-158/158-1/158-2 | Item classification identifier | Null — #2659 / SOL-85. |
| BT-159 | Item country of origin | Null — #2659 / SOL-85. |
| BT-160/BT-161 | Item attribute (BG-32) | Null (`[]`) — #2659 / SOL-85. |

## Summary of gaps this review found, beyond the design

Six terms have no source and no owning issue anywhere in the design: **BT-46/46-1, BT-47/47-1**
(buyer identifier / legal registration identifier), **BT-93** (allowance base amount), **BT-97/98**
(allowance reason / reason code), and **BT-127** (line note). **BT-116** (taxable amount) is null
unconditionally rather than only for the compound/document-level cases design §5.5 names, because
`TaxSummaryRow` carries no taxable base at all today. All of these, plus the untaxed-line gap on
BT-151/152, are flagged on SOL-84 rather than silently closed with a guess.
