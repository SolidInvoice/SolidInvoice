---
title: Searching
description: Find clients, invoices, quotes, and payments using the global search bar.
sidebar_position: 1
---

# Searching

The search bar at the top of every SolidInvoice page is a single entry point for finding any record across your account — clients, contacts, invoices, recurring invoices, quotes, and payments. Type a few characters, and matching records group by type in a dropdown. Hit `↵` (or click a row) to jump straight to a record.

You can focus the search bar from anywhere with the keyboard shortcut `Ctrl+K` (or `Cmd+K` on macOS), and there's a `?` icon at the right edge of the bar that opens a quick syntax reference modal.

:::info[Hosted vs self-hosted]
Search is **always available** on the [hosted SolidInvoice](https://solidinvoice.co) plan — there's nothing to set up.

If you're **self-hosting**, search is optional and needs to be enabled by configuring a search engine. See the [Meilisearch integration](../integrations/meilisearch.md) for the setup steps. Without it, the search bar is hidden and the rest of SolidInvoice runs normally.
:::

## What's searchable

The search bar covers six record types, scoped to the company you're currently working in:

- `Clients` — by name, website.
- `Contacts` — by name and email.
- `Invoices` — by number, client name, status, total.
- `Recurring invoices` — same fields as invoices.
- `Quotes` — by number, client name, status, total.
- `Payments` — by reference, client name, status, total.

Results from other companies you belong to are never shown — switch companies first if you need to find a record on another account.

## Free-text search

Plain words like `acme` or `invoice 1024` do a fuzzy, typo-tolerant match across all six record types. A few practical notes:

- **Multiple words** are AND-matched within a record type — `klein 5000` shows clients/invoices/etc. that match both terms.
- **Typos and partial matches** are handled — `klien` will still find `Klein-Lehner`.
- **Quotes around a phrase** force an exact-phrase match — `"Acme Corp"` won't match `Acme Holdings`.
- The search runs as you type, with a short debounce. Results refresh on every keystroke.

## Qualifiers

Beyond plain text, the search bar understands a small qualifier syntax — similar to GitHub's search — that maps to filters on each record type. Qualifiers have the form `key:value` and can be combined with free-text terms.

| Qualifier | What it does | Example |
| --- | --- | --- |
| `in:` | Limit results to one or more record types: `clients`, `contacts`, `invoices`, `recurring_invoices`, `quotes`, `payments`. | `in:invoices,quotes overdue` |
| `status:` | Filter by status (e.g. `paid`, `pending`, `draft`, `overdue`). | `status:paid acme` |
| `client:` | Filter by client name. Use quotes for multi-word names. | `client:"Acme Corp"` |
| `amount:` | Filter by total amount. | `amount:1000` |
| `created:` | Filter by creation date. | `created:2026-01-15` |
| `sort:` | Sort the results. Accepts `amount`, `amount_desc`, `date`, `date_desc`. | `unpaid sort:amount_desc` |

Qualifiers that don't apply to a given record type are silently ignored for it. For example, `client:Acme` filters invoices, quotes, payments, and recurring invoices, but has no effect on the contacts results — there, `client:Acme` falls through and is treated as part of the free-text query instead.

A few worked examples:

```text
in:invoices status:overdue sort:date_desc
client:"Acme Corp" amount:5000
in:clients,contacts john
```

The first finds the most recently created overdue invoices. The second finds anything (across all types) for the client `Acme Corp` with a total of `5000`. The third searches only the `clients` and `contacts` indexes for `john`.

## Real-time updates

When you create, edit, or delete records — through the UI, the API, or any integration — search updates immediately. There's no scheduled re-index and nothing to refresh; a record you just created is searchable on the very next keystroke.

If results ever look out of sync (for example, after a database restore on a self-hosted instance), the search engine indexes can be rebuilt — see [Meilisearch integration → Initial indexing](../integrations/meilisearch.md#initial-indexing).

## Troubleshooting

### The search bar isn't visible

This means the search engine isn't configured for your installation. On the hosted plan this shouldn't happen — contact support if you're on the hosted plan and the search bar is missing. On a self-hosted instance, follow the [Meilisearch integration](../integrations/meilisearch.md) guide to configure and connect a search engine. After setting the environment variables, clear the application cache and reload.

### A record I just created or edited isn't showing up

Indexing happens immediately on save, so this should be rare. If it does happen — typically after a manual database change or a backup restore — re-run the search engine import from the command line to rebuild the indexes from the database. The exact command and options are documented at [Meilisearch integration → Initial indexing](../integrations/meilisearch.md#initial-indexing).

### My free-text search returns nothing

Double-check you're in the right company (the company switcher is in the top right of the navigation). Search results are scoped to the active company only — a record on a different company won't appear until you switch to that company.
