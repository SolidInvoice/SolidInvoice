---
title: Overdue invoices
description: Automatically mark invoices as overdue and notify clients with escalating payment reminders.
sidebar_position: 6
---

# Overdue invoices

SolidInvoice can automatically mark unpaid invoices as overdue once their due date passes, and send escalating reminder emails to clients at configurable intervals.

## How it works

A background task runs every hour and checks all pending invoices. Any invoice whose due date has passed is transitioned to the **Overdue** status automatically. When that happens, SolidInvoice also sends an internal notification to users who subscribe to invoice alerts.

:::info
An invoice must have a due date set for the automation to act on it. Invoices with no due date are never marked overdue.
:::

## Set a due date on an invoice

When creating or editing an invoice, fill in the `Due Date` field. The date appears on the PDF and on the client-facing invoice page, and is used by both the overdue check and the reminder schedule.

See [Creating an invoice](./creating-an-invoice.md) for the full invoice form reference.

## Payment reminders

In addition to marking invoices overdue, SolidInvoice can send reminder emails to clients on a schedule. Reminders are sent to the contacts on the invoice at three intervals after the due date:

| Days overdue | Email subject |
| --- | --- |
| 1 day | Payment Reminder: Invoice `{id}` |
| 7 days | Payment Overdue: Invoice `{id}` |
| 14 days | URGENT: Invoice `{id}` — Immediate Action Required |

A pre-due reminder can also be sent a configurable number of days *before* the due date.

For full details on configuring reminders, see [Payment reminders](./payment-reminders.md).

## Configure the reminder settings

Go to **Settings → Invoice** to control the reminder behaviour.

| Setting | Default | Description |
| --- | --- | --- |
| **Enable automatic invoice payment reminders** | On | Master switch for all automated reminders |
| **Send reminder before invoice is due** | On | Send the pre-due reminder email |
| **Days before due date to send pre-due reminder** | 3 | Set to `0` to disable the pre-due reminder |

:::note
Reminder features are available on paid plans. Trial accounts can view the settings but cannot enable them.
:::

## Invoice statuses

Once marked overdue, the invoice status changes to **Overdue** in the grid and on the invoice detail page. Recording a payment for an overdue invoice transitions it to **Paid**.

See [Invoice statuses](./invoice-statuses.md) for the full status lifecycle.

## Related

- [Payment reminders](./payment-reminders.md)
- [Invoice statuses](./invoice-statuses.md)
- [Creating an invoice](./creating-an-invoice.md)
