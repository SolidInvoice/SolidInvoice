---
title: Setting up tax rates
description: Create and manage the tax rates you can apply to invoices and quotes.
sidebar_position: 1
---

# Setting up tax rates

Tax rates define the percentages or flat amounts that SolidInvoice uses when calculating tax on invoice and quote line items, and for invoice-level adjustments such as withholding tax. You must create at least one tax rate before the tax column appears on invoices.

## Navigate to Tax Rates

In the sidebar, expand **System** and click **Tax Rates**. The list shows every rate configured for your company.

## Add a tax rate

Click **Add Tax Rate** to open the creation form.

### Name

A short label that identifies this rate (e.g. `VAT`, `GST`, `Sales Tax`). Names must be unique within your company and cannot exceed 32 characters.

### Rate

The numeric value of the tax. For percentage-based types enter a number such as `20` (for 20%). For the Flat Rate type enter the fixed currency amount.

### Type

Controls how the rate is calculated relative to the item price:

| Type | Behaviour |
|---|---|
| **Inclusive** | Tax is already included in the item price — it is extracted during calculation and shown separately. |
| **Exclusive** | Tax is calculated on top of the item price and added to the total. |
| **Flat Rate** | A fixed amount is charged regardless of the item price or quantity. |

### Category

The tax category determines how the rate appears on output documents and affects how totals are presented. Available categories:

| Category | When to use |
|---|---|
| **Standard** | The default for most taxable goods and services. |
| **Zero-Rated** | Taxable at 0% (e.g. basic food items under some VAT regimes). |
| **Exempt** | Not subject to tax. The rate is still shown for transparency. |
| **Out of Scope** | Outside the tax system entirely (e.g. inter-company transactions). |
| **Reverse Charge** | The customer accounts for the tax rather than the supplier. |

### Compound

Check **Compound** to apply this rate on top of already-taxed subtotals rather than on the original price. Use this for tax-on-tax scenarios required by certain jurisdictions.

## Save

Click **Save** to create the rate. It is immediately available for selection on invoices and quotes.

## Edit or delete a rate

From the Tax Rates list, use the row actions to edit or delete an existing rate.

:::warning
Editing a rate changes it for future use only. Tax amounts on invoices and quotes that have already been issued are snapshotted at the rate in effect at the time of issue and are not retroactively updated.
:::

## Related

- [Adding company tax identifiers](./company-tax-identifiers.md)
- [Applying taxes to invoices](./applying-tax-to-invoices.md)
