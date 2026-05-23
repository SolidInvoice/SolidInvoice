---
title: Adding client tax identifiers
description: Store a client's tax registration numbers so they appear on the invoices and quotes you send them.
sidebar_position: 3
---

# Adding client tax identifiers

Client tax identifiers are your client's tax registration numbers — their VAT number, GST registration, TIN, and so on. SolidInvoice prints them on the invoices and quotes you send to that client.

## Where to add them

Tax identifiers are part of the client record. You can add them when creating a new client or at any time through the client edit form.

In the client form, scroll to the **Tax Identifiers** section (marked as optional). If no identifiers have been added yet, you will see the hint: *No tax identifiers added yet. Add one if this client has a VAT, GST or other registered tax number.*

## Add an identifier

Click **Add tax identifier**. A row appears with three fields:

| Field | Description |
|---|---|
| **Type** | The kind of identifier. Choose from `VAT`, `GSTIN`, `TIN`, `ABN`, `CNPJ`, `TRN`, or `Other`. |
| **Number** | The client's registration number for that identifier type. |
| **Primary** | Mark one identifier as primary when the client has more than one. The primary identifier is emphasised on output documents. |

Repeat for each identifier the client holds.

## Remove an identifier

Click the trash icon on the right of the row to remove it.

## Save

Save the client form as normal. All identifier changes are saved together with the rest of the client record.

:::tip
If you are registered for VAT and your client is too, recording both your own VAT number (under [Company Tax Identifiers](./company-tax-identifiers.md)) and the client's VAT number here ensures both appear on the invoice — a common requirement for business-to-business tax compliance.
:::

## Related

- [Adding company tax identifiers](./company-tax-identifiers.md)
- [Setting up tax rates](./tax-rates.md)
- [Applying taxes to invoices](./applying-tax-to-invoices.md)
