---
title: Managing quotes
description: Find, view, send, edit, clone and archive quotes in SolidInvoice.
sidebar_position: 3
---

# Managing quotes

## The quote list

Go to `Quotes` in the sidebar. The summary cards at the top count your quotes in total and how many are pending, accepted and declined.

The table lists each quote with these columns: Quote #, Client, Total, Status, Tax, Discount, Created and Valid until. Click a column header to sort by it.

Use the **Filters** button to narrow the list:

- **Status**: pick one or more statuses, for example `Expired` to see every quote that ran out before the client answered.
- **Created** and **Valid until**: pick a date range. Filter **Valid until** on the coming weeks to see which quotes are about to expire.

### Archived quotes

The `Archived` tab lists quotes you have archived. They are hidden from the `Active` tab but not deleted.

## Viewing a quote

Click `View` on a quote row to open it. The header shows the status, the date the quote was created and, if it has one, its **Valid until** date. The **Quote Summary** panel on the right repeats these, and highlights the date once a quote has expired.

The buttons at the top depend on the status. See [Quote statuses](./quote-statuses.md) for which actions each status offers. The print and PDF icons are always available, and the `···` **More Actions** menu holds Clone, Edit, Re-Open, Archive and Cancel when they apply.

## Sending a quote

- A **Draft** quote: click the dropdown next to `Publish` and choose `Publish and Send`.
- A **Pending** quote: click `Send` to email it again.

The quote is emailed to the contacts selected on it. When the quote has a **Valid until** date, the email shows it as **Expires** and the PDF shows it as **Valid Until**.

## Editing a quote

Choose `Edit` from **More Actions**. The edit form is the same as the [create form](./creating-a-quote.md). An accepted quote cannot be edited.

:::info
Editing a pending quote does not email the client. Click `Send` afterwards if they should get the updated copy.
:::

## Cloning a quote

Choose `Clone` from **More Actions** to create a new **Draft** quote with the same client, contacts, line items, discount, taxes, terms and notes. The clone gets a new quote number and no **Valid until** date, so set one before you send it.

## Archiving a quote

Choose `Archive` from **More Actions**, or tick quotes in the list and use the `Archive` bulk action. Archived quotes appear in the `Archived` tab.

## Related

- [Creating a quote](./creating-a-quote.md)
- [Quote statuses](./quote-statuses.md)
- [Quote expiry](./quote-expiry.md)
