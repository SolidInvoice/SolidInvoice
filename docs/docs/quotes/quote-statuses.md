---
title: Quote statuses
description: Understand every quote status in SolidInvoice, the actions available at each one, and how an accepted quote becomes an invoice.
sidebar_position: 2
---

# Quote statuses

Every quote has a status that shows where it is between drafting and the client's decision. The status decides which buttons the quote page shows.

## Status overview

| Status | Badge colour | Meaning |
| --- | --- | --- |
| **New** | Grey | The quote has not been saved as a draft or published yet. You rarely see this status. |
| **Draft** | Grey | Saved but not sent to the client. |
| **Pending** | Yellow | Sent to the client and waiting for their decision. |
| **Accepted** | Green | The client accepted the quote. An invoice has been created from it. |
| **Declined** | Red | The client declined the quote. |
| **Expired** | Orange | The quote was pending when its **Valid until** date passed. See [Quote expiry](./quote-expiry.md). |
| **Cancelled** | Grey | You withdrew the quote. |
| **Archived** | Purple | Moved out of the active list. |

## Draft

A draft is only visible to you, and every field can be edited.

**Available actions:** `Publish`, `Publish and Send` (in the dropdown next to `Publish`), Edit, Clone, Archive, Cancel.

`Publish` marks the quote **Pending** without emailing it. `Publish and Send` also emails it to the quote's contacts.

## Pending

The quote has been sent and you are waiting for the client.

**Available actions:** `Accept`, `Decline`, `Send`, Edit, Clone, Archive, Cancel.

- **Send** emails the quote to the client again, for example if they missed the first email.
- **Accept** and **Decline** record the client's answer. The client replies to you; the client's view of the quote has no accept or decline buttons.

If the quote has a **Valid until** date and the client does not answer in time, it moves to **Expired** automatically.

## Accepted

Accepting a quote creates a **Draft** invoice from it, with the same client, contacts, line items, discount, terms and notes. The invoice is dated today and uses the client's [payment terms](../invoices/payment-terms.md). Review it and send it as usual. See [Sending invoices](../invoices/sending-invoices.md).

**Available actions:** `View Invoice`, Clone, Archive.

An accepted quote can no longer be edited.

## Declined, Expired and Cancelled

These quotes are closed, but not final.

**Available actions:** Re-Open, Edit, Clone, Archive.

`Re-Open` moves the quote back to **Draft**, so you can change it, for example give an expired quote a new **Valid until** date, and publish it again.

## Archived

Archived quotes move from the `Active` tab of the quote list to the `Archived` tab. See [Managing quotes](./managing-quotes.md#archiving-a-quote).

## Status transitions at a glance

```
Draft → Pending       (Publish, or Save and Send)
Pending → Accepted    (Accept; creates a draft invoice)
Pending → Declined    (Decline)
Pending → Expired     (Valid until date passes, automatic)
Draft or Pending → Cancelled
Declined, Expired or Cancelled → Draft   (Re-Open)
Any status except Archived → Archived
```

## Related

- [Creating a quote](./creating-a-quote.md)
- [Quote expiry](./quote-expiry.md)
