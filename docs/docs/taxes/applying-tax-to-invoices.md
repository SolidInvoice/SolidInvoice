---
title: Applying taxes to invoices
description: Add line-level taxes per item and invoice-level adjustments such as withholding tax.
sidebar_position: 4
---

# Applying taxes to invoices

SolidInvoice supports two independent levels of tax on every invoice (and quote):

- **Line taxes** — applied to individual line items, calculated as a percentage or flat amount of each item's price.
- **Invoice taxes** — applied to the invoice as a whole, used for withholding tax, surcharges, or any adjustment that spans all line items.

:::info
The tax column and the invoice-level tax section only appear when at least one [tax rate](./tax-rates.md) has been configured for your company.
:::

## Line-level taxes

Each line item on an invoice has a **Tax** column. You can assign one or more tax rates to a single line.

### Add a tax to a line

1. On the line item row, click **+ Add tax** in the Tax column.
2. A dropdown appears — select the tax rate to apply. The dropdown shows the rate name, the percentage or flat amount, and a tag for non-standard categories (e.g. `[exempt]`, `[zero-rated]`, `[reverse charge]`).
3. To apply a second tax to the same line, click **+ Add tax** again and choose another rate.

### Remove a tax from a line

Click the remove button next to the tax row on the line item.

### Compound taxes

If a rate is marked as [compound](./tax-rates.md), it is calculated on the subtotal that includes previously applied taxes on that line rather than on the original price. The order in which taxes are listed on the line determines the calculation sequence.

## Invoice-level taxes

The **Withholding & adjustments** section sits below the line items. Use it for taxes or charges that apply to the whole invoice — for example, TDS (tax deducted at source) or a flat regulatory surcharge.

### Add an invoice tax

Click **Add invoice tax**. A row with three fields appears:

| Field | Description |
|---|---|
| **Tax** | Select a rate from your configured tax rates. The same dropdown as line taxes — shows rate, amount, and category tags. |
| **Direction** | How the tax affects the invoice total (see below). |
| **Note** | Optional free-text note printed on the invoice (e.g. `Reverse-charge VAT — recipient accounts for VAT`). |

### Direction

| Direction | Effect |
|---|---|
| **Additive** | The tax amount is added to the invoice total. Use for surcharges and additional levies. |
| **Deductive** | The tax amount is subtracted from the invoice total. Use for withholding tax (TDS) where the client remits the tax directly to the authority. |
| **Informational** | The tax is displayed on the invoice for reference only and does not change the total. Use when you are required to disclose a tax that the client handles separately. |

### Remove an invoice tax

Click the trash icon on the right of the tax row.

## Tax on quotes

The same line-tax and invoice-tax controls are available when creating a quote. Taxes configured on a quote are carried over when you [convert the quote to an invoice](../invoices/creating-an-invoice.md).

## Rate snapshots

When an invoice is issued, SolidInvoice records a snapshot of each tax rate — its name, percentage, category, and type — at that point in time. If you later edit a tax rate, the change applies only to new invoices; the tax amounts on previously issued invoices remain unchanged.

## Related

- [Setting up tax rates](./tax-rates.md)
- [Adding client tax identifiers](./client-tax-identifiers.md)
- [Creating an invoice](../invoices/creating-an-invoice.md)
