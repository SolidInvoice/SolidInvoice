---
title: Quote expiry
description: Give quotes a validity period so they expire automatically, and keep track of quotes that are about to expire.
sidebar_position: 4
---

# Quote expiry

A quote can be valid for a limited time, such as 14 or 30 days. When that time runs out before the client answers, SolidInvoice marks the quote **Expired** for you.

## Set how long a quote is valid

When you [create or edit a quote](./creating-a-quote.md#quote-details), choose an option under **Valid for**:

| Option | Valid until |
| --- | --- |
| `No expiry` | The quote never expires. This is the default. |
| `7 days`, `14 days`, `30 days`, `60 days`, `90 days` | Counted from the day you choose the option. |
| `Custom date` | Any date you pick. |

The resulting date shows on the quote page, in the quote email and on the PDF, so the client knows how long they have.

## When a quote expires

The **Valid until** date is the last day the quote is valid. A quote valid until 20 November can still be accepted on 20 November. A nightly background task marks it **Expired** after that day ends.

Only **Pending** quotes expire. Drafts, and quotes that are already accepted, declined or cancelled, keep their status even after the date passes.

When a quote expires, SolidInvoice sends the usual quote status notification to users who subscribe to it.

:::info
On a self-hosted install, automatic expiry needs the background worker running. See [Cron job setup](../installation-guide/distribution-package/cron-job-setup.md).
:::

## Quotes expiring soon

The **Attention Required** card on the dashboard has an **Expiring Quotes** section. It lists pending quotes that expire within the next 7 days, soonest first, and shows each quote's last valid day.

To see more than the dashboard shows, open the quote list and filter **Valid until** on a date range. See [Managing quotes](./managing-quotes.md#the-quote-list).

## Extend or re-issue an expired quote

An expired quote can't be accepted. To offer it again:

1. Open the quote and choose `Re-Open` from **More Actions**. The quote goes back to **Draft**.
2. Click `Edit` and choose a new **Valid for** period or date.
3. Publish or send the quote again.

You can also clone the expired quote, which leaves the original as it is. See [Cloning a quote](./managing-quotes.md#cloning-a-quote).

## Related

- [Quote statuses](./quote-statuses.md)
- [Creating a quote](./creating-a-quote.md)
