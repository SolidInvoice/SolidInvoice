---
title: Understanding the schedule
description: How the recurring invoice schedule decides when invoices get generated.
sidebar_position: 2
---

# Understanding the schedule

A recurring invoice is generated when three things line up: today's date matches the schedule, the recurring invoice is in the `Active` state, and SolidInvoice's background scheduler runs. This page explains each piece.

## How generation works

SolidInvoice's background scheduler runs **every hour**. On each run, it:

1. Finds every active recurring invoice.
2. Checks whether today's date is one of the schedule's matching dates.
3. If a date matches and an invoice hasn't already been generated for that day, it creates one.

That last step means a recurring invoice will only ever produce one invoice per matching day, even if the scheduler runs many times.

:::warning
The scheduler is what does the work — without it running, no invoices are generated. The [Cron job setup guide](../installation-guide/distribution-package/cron-job-setup.md) covers how to set it up on each platform. The Homebrew, Docker, and quick-install paths run it automatically.
:::

## Recurring types

The `Recurring Type` field decides what counts as a "matching day". Each type asks for a different follow-up.

### Daily

Generates an invoice every day from the start date onward. No follow-up field — once it's `Active`, it generates daily.

### Weekly

Reveals a `Repeats on` row of checkboxes for `Monday` through `Sunday`. Tick one or more days. An invoice is generated on each ticked day every week.

For a once-a-week subscription, tick a single day. For an every-weekday schedule, tick `Monday` through `Friday`.

### Monthly

Reveals a `Days of the month` multi-select with values `1st` through `31st`. Pick one or more. An invoice is generated on each picked day every month.

:::note
If you pick `31st` and a month has 30 or fewer days, no invoice is generated for that month on that date — the day simply doesn't exist in that month.
:::

### Yearly

Reveals two fields:

- **`Repeats in months`** — checkboxes for `January` through `December`. Pick one or more.
- **`Day of month`** *(optional)* — a dropdown with `1st` through `31st`. If left blank, the schedule uses the day from the `Start Date`.

A yearly schedule generates one invoice per chosen month per year, on the chosen (or inherited) day.

## End conditions

The `End Recurrence` field controls when the schedule stops.

- **`Never`** — invoices are generated indefinitely until you pause, cancel, or archive the recurring invoice.
- **`On the following date`** — reveals an `End Date` picker (must be in the future). The schedule stops *on or after* that date and the recurring invoice is automatically marked `Complete`.
- **`After x occurrences`** — reveals an `End After Occurrences` number field. Once that many invoices have been generated, the schedule stops and the recurring invoice is marked `Complete`.

`Complete` is the *natural* end state — it's set automatically when one of the end conditions is reached. To stop a schedule manually, see [Managing the schedule](./managing-the-schedule.md).

## What you see on the view page

The recurring invoice's view page shows a `Recurring Schedule` card summarising the configuration in plain English (for example, `Every Monday`), the `Start Date`, and the `End Date` if one is set.

While the recurring invoice is `Active`, an `Upcoming Occurrences` card lists the next few dates the scheduler is going to fire — useful for verifying the schedule does what you expected.
