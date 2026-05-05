---
title: Meilisearch
description: Power SolidInvoice's global search with a Meilisearch instance.
sidebar_position: 2
---

# Meilisearch

SolidInvoice uses [Meilisearch](https://www.meilisearch.com/) to power the global search bar in the top navigation. When a Meilisearch instance is configured, you can search across clients, contacts, invoices, recurring invoices, quotes, and payments from a single query box, with typo tolerance and per-entity filters.

The integration is entirely optional — without it, SolidInvoice runs normally and the search bar is hidden.

## How it works

- Six indexes are maintained, one per searchable entity: `clients`, `contacts`, `invoices`, `recurring_invoices`, `quotes`, `payments`.
- Records are scoped per company. Every searchable entity is indexed with a `companyId`, and queries are filtered to the active company so users only see results from their own data.
- New, updated, and deleted records are indexed in real time via Doctrine lifecycle listeners — there is no scheduled re-index for normal operation.
- The global search bar in the top navigation only appears when both the Meilisearch URL and API key are configured.

## Prerequisites

You need a running Meilisearch server (v1.x). Common options:

- **Self-hosted** — install via [the official binary, Docker image, or package manager](https://www.meilisearch.com/docs/learn/getting_started/installation).
- **Meilisearch Cloud** — managed hosting; provides a URL and API key out of the box.

The server needs to be reachable from the SolidInvoice application over HTTP. For self-hosted deployments, this is usually a private network address or `http://localhost:7700`.

:::warning
Always set a master key on your Meilisearch server in production (`MEILI_MASTER_KEY` on the Meilisearch side). Running with no master key exposes write access to anyone who can reach the HTTP port.
:::

## Configuration

The integration is configured through three environment variables, set the same way you set any other SolidInvoice environment variable (Docker `-e` flag, or a `.env` file in the application root for the distribution package).

| Variable | Default | Description |
| --- | --- | --- |
| `SOLIDINVOICE_MEILISEARCH_URL` | *(empty)* | Base URL of your Meilisearch instance, e.g. `http://meilisearch:7700`. Leave empty to disable the integration. |
| `SOLIDINVOICE_MEILISEARCH_API_KEY` | *(empty)* | An API key with read/write access to the indexes. Use the master key during setup, then switch to a scoped key once the indexes exist (see [Security](#security)). |
| `SOLIDINVOICE_MEILISEARCH_PREFIX` | `solidinvoice_<env>_` | Prefix prepended to every index name. The default keeps `dev`, `test`, and `prod` indexes separate when sharing one Meilisearch server. |

A typical production configuration:

```ini title=".env"
SOLIDINVOICE_MEILISEARCH_URL=http://meilisearch.internal:7700
SOLIDINVOICE_MEILISEARCH_API_KEY=your-meilisearch-api-key
SOLIDINVOICE_MEILISEARCH_PREFIX=solidinvoice_prod_
```

Restart the application after changing any of these values.

:::info
The search bar is shown only when both `SOLIDINVOICE_MEILISEARCH_URL` and `SOLIDINVOICE_MEILISEARCH_API_KEY` are non-empty. If you've configured the variables but the search bar still doesn't appear, clear the application cache: `bin/console cache:clear`.
:::

## Initial indexing

After configuring the environment variables for the first time — and any time you import data outside the SolidInvoice UI (for example, from a database backup or a migration from another tool) — you'll need to populate the indexes manually.

Create the indexes with the configured settings:

```bash
bin/console meilisearch:create
```

Then import your existing data:

```bash
bin/console meilisearch:import
```

This walks every searchable entity in the database and pushes it to Meilisearch. For large datasets, you can tune the batch size and request timeout:

```bash
bin/console meilisearch:import --batch-size=500 --response-timeout=10000
```

To re-import only specific entities, pass `--indices` with a comma-separated list of index names:

```bash
bin/console meilisearch:import --indices=invoices,clients
```

:::tip
For zero-downtime re-indexing on a live system, use `--swap-indices`. Meilisearch indexes into temporary indexes and atomically swaps them in once the import completes, so users never see partial results during the rebuild.

```bash
bin/console meilisearch:import --swap-indices
```

:::

After the initial import, day-to-day changes are picked up automatically — there's no need to re-run `meilisearch:import` when users create, edit, or delete records through the UI or API.

## Maintenance commands

The Meilisearch search bundle ships with a small set of commands for managing the indexes. All of them respect the configured prefix.

| Command | Purpose |
| --- | --- |
| `bin/console meilisearch:create` | Create the indexes and apply their configured settings (filterable/sortable attributes). Safe to run repeatedly. |
| `bin/console meilisearch:import` | Bulk-import every entity into its index. See [Initial indexing](#initial-indexing). |
| `bin/console meilisearch:update-settings` | Push only the settings (filterable/sortable attributes, etc.) without re-indexing documents. Use after upgrading SolidInvoice if the bundled index settings have changed. |
| `bin/console meilisearch:clear` | Remove all documents from the indexes but keep the indexes themselves. |
| `bin/console meilisearch:delete` | Delete the indexes entirely. You'll need to run `meilisearch:create` and `meilisearch:import` again afterwards. |

Each command accepts `--indices=<list>` to scope it to a subset of indexes.

## Using the search bar

Once Meilisearch is configured and indexed, the search bar appears in the top navigation of every page. The query syntax (free-text, qualifiers like `in:`, `status:`, `client:`, `sort:`, etc.), what's searchable, and worked examples are documented at [Searching](../using-solidinvoice/searching.md) — that page covers everything end users need.

Indexing happens in real time: when a record is created, updated, or deleted through the UI, the API, or the MCP server, the change is dispatched to Meilisearch as part of the same request. There is no replication delay beyond Meilisearch's own indexing time (typically milliseconds), and no scheduled re-index for normal operation.

If indexes ever drift out of sync — for example, after a database restore — re-run `bin/console meilisearch:import` to rebuild from the canonical data in the database (see [Initial indexing](#initial-indexing)).

## Security

The API key SolidInvoice uses needs both read and write access to the indexes. The simplest setup is to use the Meilisearch master key, but for production you should generate a [scoped API key](https://www.meilisearch.com/docs/learn/security/master_api_keys) limited to the indexes that match your configured prefix.

When generating a scoped key, grant it the following actions on indexes matching `<prefix>*`:

- `documents.add`, `documents.delete`, `documents.get` — for real-time indexing and search.
- `indexes.create`, `indexes.update`, `indexes.delete` — for the management commands.
- `settings.update`, `settings.get` — for `meilisearch:update-settings`.
- `search` — for the global search bar.

If you don't plan to run the maintenance commands from the application server (for example, you run them from a separate ops host), you can issue a more restrictive key for the application itself that grants only `search`, `documents.add`, `documents.delete`, and `documents.get`.

:::warning
Per-company isolation is enforced by SolidInvoice through the `companyId` filter on every query, not by Meilisearch itself. Anyone with direct access to the Meilisearch HTTP API and a valid key can read across all companies' data. Treat the Meilisearch endpoint as you would the application database — keep it on a private network and restrict the API key.
:::

## Disabling the integration

To turn the search off, clear the URL or API key:

```ini title=".env"
SOLIDINVOICE_MEILISEARCH_URL=
SOLIDINVOICE_MEILISEARCH_API_KEY=
```

Restart the application. The search bar disappears, and SolidInvoice stops dispatching updates to Meilisearch. Existing indexes on the Meilisearch server are left in place — delete them manually with `bin/console meilisearch:delete` (run before clearing the env vars) or directly through the Meilisearch dashboard if you no longer need them.
