---
title: Client currency
description: How the per-client currency choice flows through to quotes, invoices, payments, and credit.
sidebar_position: 5
---

# Client currency

Each client in SolidInvoice has a currency. The currency determines what unit you bill the client in — every quote, invoice, payment, and credit adjustment for that client is denominated in their chosen currency.

## Where it's set

The client's currency is set on the [create](./create-new-client.md) and edit forms, in the `Client Info` card, as the `Currency code` dropdown.

The dropdown lists [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency codes (USD, EUR, GBP, ZAR, JPY, etc.). The placeholder option is `System Default`, which is what you'll see if you haven't picked a currency for the client yet.

## System default vs explicit choice

- **`System Default`** *(no explicit selection)* — the client inherits your company's default currency at the moment a quote, invoice, or payment is created. If you change the company default later, future documents for this client will use the new default.
- **An explicit currency** *(USD, EUR, …)* — the client is locked to that currency regardless of the company default. Useful when most of your clients pay in your local currency but a handful pay in another.

The company default is configured in `System` → `Settings` → `Currency`. Pick the value that fits the majority of your clients there, then only override on the individual clients that differ.

:::info
Clients without an explicit currency follow your company default *dynamically*. If you change the default from USD to EUR, every client still on `System Default` will start using EUR for new documents. Existing invoices and quotes keep their original currency — only future documents are affected.
:::

## What the currency drives

Once set, the client's currency surfaces in several places:

- **Quotes and invoices** — the currency symbol and code shown on the document, the unit for line items, taxes, discounts, and the total.
- **Payments** — every payment recorded against the client (including those that apply [credit](./client-credit.md)) is in the client's currency.
- **Credit balance** — the prepaid credit you hold for the client is denominated in their currency.
- **The client list** — the `Currency` column on `/clients` shows the resolved currency (the client's explicit choice if set, otherwise the system default).
- **Stats and totals** — the `Total Income`, `Outstanding`, and per-client totals on the dashboard and the client view page are in the client's currency.

## Changing a client's currency

Currency can be changed from the client's edit form, but **be careful**:

:::warning
Changing the currency on a client that already has invoices, quotes, or a credit balance does **not** convert any historical amounts. Existing documents keep their original currency, while everything created from that point forward uses the new currency. You can end up with a client whose history mixes two currencies — confusing to reconcile.

If you genuinely need to switch a client to a new currency, archive the old client and create a new one in the new currency.
:::

## Multi-currency in one workspace

You can have clients in different currencies inside a single SolidInvoice company — there's no requirement to keep everyone on the same one. Each client's documents and stats are kept in their own currency. SolidInvoice does **not** convert between currencies on dashboards or reports — totals are shown per currency rather than aggregated into a single number.

If you operate in materially different currencies and want clean per-currency books, the cleanest separation is to use [companies](../companies/overview.md) — one company per currency — and switch between them as needed.

## Custom currency codes

The currency dropdown is fixed to the published ISO 4217 list. Cryptocurrencies, in-house tokens, and other non-ISO codes are not supported — the field validates that the value is exactly three characters and matches a known currency code.
