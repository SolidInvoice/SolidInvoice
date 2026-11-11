---
title: Creating a quote
description: Create a new quote in SolidInvoice, set how long it is valid, and send it to a client.
sidebar_position: 1
---

# Creating a quote

To create a new quote, go to `Quotes` in the sidebar and click `Create Quote`, or use the global `+ Create` button at the top of the page.

## Choose a client

Pick who the quote is for:

- **Existing**: choose a client from the dropdown. All of the client's contacts are selected to receive the quote, and you can untick any of them.
- **NewClient**: create a client on the spot by entering their name, the contact's first name, last name and email address. The client is saved when you save the quote.

## Quote details

| Field | Required | Description |
| --- | --- | --- |
| **Quote #** | Auto | Generated from your numbering settings. Click the pencil icon to change it for this quote. |
| **Valid for** | Yes | How long the quote stays valid: `No expiry`, `7 days`, `14 days`, `30 days`, `60 days`, `90 days` or `Custom date`. Defaults to `No expiry`. |
| **Valid until** | Only for `Custom date` | The last day the quote is valid. Filled in for you when you choose a period, and hidden when you choose `No expiry`. |
| **Discount** | No | A quote-wide discount, as a percentage or a fixed amount. |

A period counts from the day you choose it. For example, choosing `30 days` on 6 November sets **Valid until** to 6 December. To pick a different date, choose `Custom date` and set **Valid until** yourself.

Once the **Valid until** date has passed, a sent quote expires automatically. See [Quote expiry](./quote-expiry.md).

## Line items

Every quote needs at least one line item. Click `Add Item` to add a row, then fill in the item, its price, quantity and, optionally, a tax rate. The totals in the summary update as you type.

:::info
Tax is applied per line item, so different lines can carry different tax rates. Tax rates are managed under [Taxes](../taxes/tax-rates.md).
:::

## Terms and notes

Expand **Terms & Notes** at the bottom of the form to add them:

- **Terms** appear on the quote and are visible to the client.
- **Notes** are for your own records. They are not shown to the client.

## Saving the quote

Open the dropdown next to `Save as Draft` to see every save option:

| Option | What it does |
| --- | --- |
| **Save as Draft** | Saves the quote with the **Draft** status. You can keep editing it. |
| **Publish** | Saves the quote and marks it **Pending**, without emailing it. |
| **Save and Send** | Saves the quote, marks it **Pending** and emails it to the selected contacts. |

When you edit an existing quote, the main button is `Update`, with the same `Publish` and `Save and Send` options in its dropdown.

## Related

- [Quote statuses](./quote-statuses.md)
- [Managing quotes](./managing-quotes.md)
- [Quote expiry](./quote-expiry.md)
