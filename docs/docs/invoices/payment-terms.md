---
title: Payment terms
description: Set when an invoice is due, such as Net 30 or Due on receipt, and let SolidInvoice fill in the due date.
sidebar_position: 7
---

# Payment terms

Payment terms tell your client how long they have to pay. Pick a term on the invoice and SolidInvoice works out the due date from the invoice date, then prints the term next to the due date on the invoice, PDF and email (for example `Mar 2, 2026 · Net 30`).

## The available terms

| Term | Due date |
| --- | --- |
| **Due on receipt** | The invoice date |
| **Net 7**, **Net 14**, **Net 15**, **Net 30**, **Net 45**, **Net 60**, **Net 90** | That many days after the invoice date |
| **End of month** | The last day of the month the invoice is dated in |
| **End of next month** | The last day of the following month |
| **Custom** | Whatever date you enter, or none |

All terms count from the **invoice date**, not from the day your client opens the invoice.

## Choose terms on an invoice

On the invoice form, pick a value in `Payment terms`, next to the invoice date. The `Due Date` field fills itself in and is locked, and it moves if you change the invoice date.

To set your own due date, choose **Custom**. The `Due Date` field becomes editable again.

:::info
Invoices created before payment terms existed are set to **Custom**, so their due dates are unchanged.
:::

## Set a company default

Go to **Settings → Invoice** and choose `Payment terms`. New invoices start with this term. The default is **Net 30**.

## Set terms for a client

To give one client different terms, edit the client and choose `Payment terms`. Leave it on `Company default` to follow the setting above.

When you pick that client on a new invoice, their terms are selected for you. You can still change them on the invoice.

## Recurring invoices and quotes

- **Recurring invoices** have their own `Payment terms` field. Each invoice the schedule creates gets a due date counted from the day it is issued.
- **Quotes** converted into an invoice take the client's terms, or the company default.

## Related

- [Creating an invoice](./creating-an-invoice.md)
- [Overdue invoices](./overdue-invoices.md)
- [Creating a recurring invoice](../recurring-invoices/creating-a-recurring-invoice.md)
