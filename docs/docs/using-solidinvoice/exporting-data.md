---
title: Exporting data
description: Export individual grids or a full copy of your company data as CSV, JSON, or XML.
sidebar_position: 5
---

# Exporting data

SolidInvoice lets you export data in two ways: download the rows from any grid directly, or request a complete export of everything in your company.

## Grid export

Any data grid — invoices, quotes, clients, payments, and others — has an export option. The export uses whatever filters and search terms are currently active, so you can narrow the data before downloading.

Look for the export button in the grid toolbar. Choose your format:

| Format | Use it when… |
| --- | --- |
| **CSV** | You want to open the data in a spreadsheet app |
| **JSON** | You need to process it programmatically |
| **XML** | Your system requires structured markup |

The file downloads immediately.

## Full company data export

A full export packages everything in your company — clients, contacts, invoices, quotes, payments, and more — into a single downloadable archive.

1. In the left sidebar, click your name to open the profile menu, then choose `Data Export`. You can also navigate to `/profile/exports`.
2. Click `Request Export`.
3. SolidInvoice queues the export as a background job. You'll receive an email notification when it's ready.
4. Return to the `Data Export` page and click `Download` next to the completed export.

:::info
Large datasets can take a few minutes to process. The export runs in the background so you can keep working in SolidInvoice.
:::

## Related

- [User profile](./user-profile.md)
- [Managing invoices](../invoices/managing-invoices.md)
