---
title: REST API
description: Authenticate and use the SolidInvoice REST API.
sidebar_position: 1
---

# REST API

SolidInvoice exposes a REST API at `/api/*` that mirrors the web UI: clients, invoices, quotes, payments, recurring invoices, taxes, and more. Authentication is via a per-user API token. The full endpoint reference is auto-generated and served by your SolidInvoice instance at `/api/docs`.

:::tip
Prefer a flexible query language over fixed endpoints? See the [GraphQL API](./graphql.md) for an alternative way to access the same data.
:::

## Creating an API token

Sign in to SolidInvoice and open `Settings` → `API Keys` (or visit `/profile/api` directly). Click the green `+ Create Token` button at the top right of the list.

![The API Tokens page with the stats row, info banner, Create Token button, and one existing token](/img/api/api-tokens-page.png)

In the `Create New API Token` dialog, fill in:

- **`Name`** *(required)* — a label for the token. Pick something that identifies where it will be used, e.g. `Reporting integration` or `Zapier`.
- **`Description`** *(optional)* — a longer note describing the token's purpose.

![The Create New API Token dialog with Name and Description fields](/img/api/create-token-modal.png)

Click `Save`. The dialog updates to show the generated token value:

![The success state with the token value, copy button, and warning that the token will not be shown again](/img/api/token-created-modal.png)

:::warning
The token is shown **only once**, immediately after creation. Click the `Copy` button and store it in a password manager or your integration's secret store before clicking `I have copied the token`. If you lose it, revoke the token and create a new one — there is no way to retrieve the original value later.
:::

## Viewing your tokens

The token list shows everything you've created, with these columns:

- `Name`, `Description` — what you entered when creating the token.
- `Usage Count` — total number of API requests made with this token.
- `Last Used` — when the token was last used to make a request, or empty if never used.
- `Created` — when the token was generated.

The four stat cards above the list summarise the same data across all your tokens: `Active Tokens`, `API Calls This Month`, `Last Activity`, and `Most Used Token`.

The list is searchable and sortable. The token value itself is never shown again after creation — only its name.

## Viewing request history

Every successful API request authenticated with a token is recorded against that token. Click `View History` on a token's row in the list to open a modal with the captured requests.

![The API Request History modal listing recent calls with method, endpoint, status, IP address, and user agent](/img/api/token-history-modal.png)

Each row records:

- `Date` — when the request arrived.
- `Method` — `GET`, `POST`, `PATCH`, `PUT`, or `DELETE`.
- `Endpoint` — the path that was called (e.g. `/api/invoices`).
- `Status` — the HTTP status code returned to the client.
- `IP Address` — the client's IP at the time of the request.
- `User Agent` — the `User-Agent` header sent by the client.

The history list is filterable by date range, method, and status range, and is capped at the 100 most recent entries displayed at a time. Failed authentication attempts (no token or wrong token) are **not** recorded — only successful ones.

## Revoking a token

To revoke a token, tick its checkbox in the list, then choose `Revoke` from the batch-actions toolbar.

:::warning
Revocation is **immediate** and there is no confirmation dialog. The token row is deleted along with its full request history. Any application using the revoked token will start receiving `401 Unauthorized` on its next request — so plan to update integrations before you revoke.
:::

If you need to rotate a token without downtime, create the new token first, switch your integration over to the new value, verify it's working (look for the new token's `Last Used` timestamp updating), and only then revoke the old token.

## Authenticating requests

Send the token in the `X-API-TOKEN` HTTP header on every request:

```bash
curl -H "X-API-TOKEN: <your-token>" \
     -H "Accept: application/ld+json" \
     https://your-instance.example/api/invoices
```

The API is **stateless** — there is no session, no CSRF token, and no login round-trip. Send the header on every request. Tokens are scoped to one user *and* one company; if your account belongs to multiple companies, generate a separate token per company by switching companies in the UI before creating the token.

If a request lacks a valid token, the server responds with `401 Unauthorized` and a JSON body:

```json
{ "message": "No API token provided" }
```

## Response formats

The API supports content negotiation via the `Accept` header. Available formats:

| Accept value | Format |
| --- | --- |
| `application/ld+json` (default) | JSON-LD with Hydra hypermedia |
| `application/json` | Plain JSON |
| `application/hal+json` | HAL JSON |
| `application/vnd.api+json` | JSON:API |
| `application/xml` or `text/xml` | XML |

Collection endpoints are paginated with **30 items per page** by default. Override with the `itemsPerPage` query parameter (at most 100):

```bash
curl -H "X-API-TOKEN: <your-token>" \
     "https://your-instance.example/api/invoices?page=2&itemsPerPage=50"
```

Clients, contacts, invoices, quotes, recurring invoices and payments include `created` and `updated` timestamps, always in UTC. To fetch only what changed since your last sync, filter and sort on them:

```bash
curl -H "X-API-TOKEN: <your-token>" \
     "https://your-instance.example/api/invoices?updated[after]=2026-10-01T00:00:00Z&order[updated]=asc"
```

Invoices and quotes can also be searched by number and client name (`?invoiceId=INV-10`, `?quoteId=Q-3`, `?client.name=acme`), and invoices, quotes and recurring invoices include a read-only `currencyCode`: the currency the document was issued in, or the client's current currency while it is still a draft.

Errors are returned in [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807) `application/problem+json` format with a human-readable `title`, `detail`, and a machine-readable `type`.

## Money format and API versions

Send an `Api-Version: 2026-10` header to get amounts as **decimal strings in the currency's own units and precision**:

| Currency | Amount |
| --- | --- |
| USD (2 decimals) | `"1250.00"` |
| JPY (no decimals) | `"1250"` |
| BHD (3 decimals) | `"12.500"` |

This applies to every amount: document totals, balances, line prices and totals, tax amounts, money discounts, client credit and payment amounts. Quantities are decimal strings too (`"1.5"`), so no precision is lost. On input, amounts may be numbers or strings in the same units; an amount with more decimal places than its currency allows (`"12.5"` for JPY) is rejected with `400 Bad Request` rather than rounded. An unknown `Api-Version` is also a `400`.

```bash
curl -H "X-API-TOKEN: <your-token>" \
     -H "Api-Version: 2026-10" \
     "https://your-instance.example/api/invoices"
```

Without the header the original format is used: amounts are JSON numbers scaled by a fixed 100, which is only correct for two-decimal currencies. That format is deprecated. Every response served in it carries `Deprecation` and `Sunset` headers, and it will be removed after **1 May 2027**. Send `Api-Version: 2026-10` on all requests to move to the new format.

## Document PDFs and public links

`GET /api/invoices/{id}/pdf` and `GET /api/quotes/{id}/pdf` return the document as a PDF, rendered with the same template as the page your client sees. Send `Accept: application/pdf` (or `*/*`):

```bash
curl -H "X-API-TOKEN: <your-token>" -H "Accept: application/pdf" \
     -o invoice.pdf https://your-instance.example/api/invoices/<id>/pdf
```

The response is `inline` with a file name of `invoice_<number>.pdf` or `quote_<number>.pdf`, and carries an `ETag`. Send that value back in `If-None-Match` and you get `304 Not Modified` until the document changes. A document of another company returns `404`.

Invoice and quote responses also include read-only links:

| Field | Value |
| --- | --- |
| `publicUrl` | The public page of the document, the same link sent to your client by email. |
| `pdfUrl` | The public PDF of the document. |
| `payUrl` | Invoices only. The page where your client pays online, or `null` when no online payment method is enabled. |

## Rate limits

The API is rate-limited to **300 requests per minute**, using a sliding window. The bucket is keyed by token when authenticated, falling back to client IP for unauthenticated requests.

Every API response includes the current state of your bucket in the headers:

| Header | Meaning |
| --- | --- |
| `X-RateLimit-Limit` | The total budget per window (`300`). |
| `X-RateLimit-Remaining` | Requests left in the current window. |
| `X-RateLimit-Reset` | Unix timestamp when the budget resets. |

If you exceed the limit, the response is `429 Too Many Requests` with a `Retry-After` header and an `application/problem+json` body. Back off until `Retry-After` seconds have elapsed before retrying.

## Retrying requests safely

Creating a client, invoice or quote, and recording a payment (`POST /api/invoices/{id}/payments`), accept an `Idempotency-Key` header. Send a new UUID with each logical request and reuse it when you retry, so a retry after a timeout does not create a second record:

```bash
curl -X POST -H "X-API-TOKEN: <your-token>" \
     -H "Content-Type: application/ld+json" \
     -H "Idempotency-Key: 9b2f6c1e-3d4a-4f5b-8c7d-1e2f3a4b5c6d" \
     -d '{"name": "Acme"}' \
     https://your-instance.example/api/clients
```

| Situation | Response |
| --- | --- |
| First request with the key | Runs normally. A `2xx` response is kept for 24 hours. |
| Same key, same request | The stored response is returned again, with `Idempotency-Replayed: true`. Nothing is created. |
| Same key, different method, path or body | `422`, problem `type` ending in `idempotency_key_mismatch`. |
| Same key while the first request is still running | `409`. Retry shortly. |
| Key that is not a UUID | `400`. |

Keys are scoped to your user and company. A failed (non-`2xx`) response is not kept, so you can retry it with the same key. Requests without the header behave as before.

## Archiving clients

`POST /api/clients/{id}/archive` archives a client and `POST /api/clients/{id}/restore` makes it active again. Neither takes a body; both return the client with `200`. Archiving an archived client, or restoring an active one, is not an error and returns `200`.

Archived clients are hidden from the API: `GET /api/clients` leaves them out and `GET /api/clients/{id}` returns `404`. Use `restore` to bring one back.

## Invoice and quote numbers

When you create an invoice or quote without an `invoiceId` (or `quoteId`), it gets the next number from your numbering settings, the same as in the app. A number you send is kept as is. Numbers are unique within your company, archived documents included: reusing one returns `422` with a violation on `invoiceId` (or `quoteId`).

`GET /api/next-number?type=invoice` (or `type=quote`) shows the number the next create would get:

```json
{"type": "invoice", "number": "42"}
```

This is a preview, not a reservation: a document created in the meantime takes that number. A missing or unknown `type` returns `422`.

## Cloning invoices and quotes

`POST /api/invoices/{id}/clone` and `POST /api/quotes/{id}/clone` copy a document, with its lines, contacts, discount, taxes, notes and terms, into a new draft. They take no body and return the new document with `201`. The copy gets a new number and today's date; an invoice's due date is worked out again from its payment terms. A document of another company returns `404`.

## Sending invoices and quotes

`POST /api/invoices/{id}/send` and `POST /api/quotes/{id}/send` email a document to the client. The body is optional; every field has a default:

```json
{
  "to": ["/api/clients/{clientId}/contact/{contactId}"],
  "cc": ["accounts@example.com"],
  "subject": null,
  "message": "Thanks for having us today.",
  "attachPdf": true,
  "copyToMe": false
}
```

| Field | |
| --- | --- |
| `to` | Contacts of the document's client. Defaults to the document's contacts. On the first send they also become its contacts; on a re-send they only receive that email. |
| `cc` | Extra email addresses. |
| `subject` | Replaces your configured email subject for this send only. |
| `message` | A personal note shown above the usual email text. |
| `attachPdf` | Attach the PDF. Defaults to `true`. |
| `copyToMe` | Also send a copy (Bcc) to you. |

The first send of a new or draft invoice moves it to `pending`, and of a draft quote too. Sending a `pending` (or, for invoices, `overdue`) document again re-sends the email without changing its status. Replies go to your own email address. The response is the document with `200`, plus `sentTo`, the addresses the email went to.

| Situation | Response |
| --- | --- |
| Any other status (paid, cancelled, accepted...) | `409` |
| Nobody to send to | `422`, violation with `code` `no_recipients` |
| A `to` that is not a contact of the document's client | `422`, violation with `code` `unknown_contact` |
| A `cc` that is not an email address, or more than 10 of them | `422` |
| More than 5 sends of the same document, or more than 500 recipients across your company, within an hour | `429`, with `Retry-After` |
| Your email address is not verified yet (hosted) | `403`, unless the only recipient is your own address |
| A document of another company | `404` |

Both endpoints accept an `Idempotency-Key` header (see [Retrying requests safely](#retrying-requests-safely)).

### Payment reminders

`POST /api/invoices/{id}/reminder` emails the client the same reminder as **Send reminder** in the app. It takes no body and returns the invoice with `200` and `sentTo`. Only `pending` and `overdue` invoices can be reminded (`409` otherwise), and only once an hour per invoice: a second reminder within the hour returns `429` with a `Retry-After` header.

### Marking an invoice as sent

`POST /api/invoices/{id}/mark-sent` marks an invoice as sent without emailing it, for when you shared the link or the PDF yourself. The optional body says how: `{"channel": "whatsapp"}`, with `whatsapp`, `sms`, `link` (the default) or `print`. A new or draft invoice moves to `pending`; a `pending` or `overdue` invoice keeps its status and returns `200`. Any other status returns `409`.

## Recording a payment

`POST /api/invoices/{id}/payments` records a payment you received outside SolidInvoice, the same way as recording it in the app:

```json
{
  "amount": "150.00",
  "method": "cash",
  "paidAt": "2026-10-14",
  "reference": "EFT 8812",
  "notes": "Paid on site",
  "currency": "USD"
}
```

| Field | |
| --- | --- |
| `amount` *(required)* | The amount in the invoice currency, as a decimal string or number (`"150.00"`, `"1250"` for JPY, `"12.500"` for BHD). More decimals than the currency has returns `422`; nothing is rounded. Must be more than zero. |
| `method` | The `gatewayName` of an enabled offline payment method, such as `cash`, `bank_transfer`, `cheque`, `card_terminal`, `credit` or one you added yourself. Defaults to `cash`; without a usable `cash` method, the first other enabled offline method by name (never `credit`). See `GET /api/payment-methods`. |
| `paidAt` | The date the payment was received, `YYYY-MM-DD`. Defaults to today. It cannot be in the future, or more than a year before the invoice date. |
| `currency` | Optional. When sent, it must be the invoice currency. |
| `reference`, `notes` | Optional free text. `reference` holds up to 255 characters. |

A payment that does not cover the balance only reduces it, and the invoice stays open. The payment that settles it marks the invoice paid. Paying more than the balance marks the invoice paid too, and the difference is added to the client's credit: the response's `creditAdded` holds that amount, and is `0` otherwise. A `credit` payment cannot be more than the client's available credit or the invoice balance.

Only `pending` and `overdue` invoices take payments. Any other invoice returns `409`, with a problem `type` ending in:

#### `invoice_not_issued`

The invoice is still new or a draft. Issue it first.

#### `invoice_not_payable`

The invoice is paid, cancelled or archived.

An invalid `amount`, `method`, `paidAt` or `currency` returns `422` with a violation on that field. An invoice of another company returns `404`.

:::warning
An integer `amount` (`1000`) is read the old way, in minor units (cents). It still works, but is deprecated: the response carries a `Deprecation` header and a `Link` to this section. Send a decimal string instead.
:::

## Voiding a payment

`POST /api/payments/{id}/void` cancels a payment you recorded by hand and returns it with `status` `cancelled`. It takes no body. The payment is kept, and the invoice balance goes back up. If the payment had settled the invoice, the invoice goes back to `pending`, or `overdue` when its due date has passed; no notification is sent again. Credit moves back too: the excess an overpayment added is taken off the client, and a `credit` payment is refunded to it.

The payment cannot be voided, and the response is `409` with a problem `type` ending in `payment_not_voidable`, when:

#### `payment_not_voidable`

- it was taken by an online gateway (refund it with the gateway instead),
- it is not a completed (`captured`) payment, for example it was already voided,
- it was paid more than 30 days ago, or
- the client has already spent the credit it added.

## Payment methods

`GET /api/payment-methods` lists your company's payment methods, and `GET /api/payment-methods/{id}` returns one. They are read-only, and gateway credentials are never included.

| Field | |
| --- | --- |
| `gatewayName` | The identifier to send as `method` when recording a payment. |
| `name` | The name shown in the app. |
| `factoryName` | The gateway type, `offline` for methods recorded by hand. |
| `internal` | Only available inside the app, not to clients paying online. |
| `enabled` | Whether the method can be used. |
| `online` | Charged through a payment gateway. Payments can only be recorded against methods where this is `false`. |

Payments show their method as `{"gatewayName": "cash", "name": "Cash"}`.

## Endpoint reference

The interactive Swagger UI for your installation is the authoritative reference — it always reflects the exact resources and fields available on your version:

```text
https://your-instance.example/api/docs
```

The same documentation for the latest public release is hosted at [solidinvoice.app/api/docs](https://solidinvoice.app/api/docs).

The main resource roots are:

- `/api/invoices` and `/api/recurring-invoices`
- `/api/quotes`
- `/api/clients`, `/api/contacts`, and `/api/addresses`
- `/api/payments` and `/api/payment-methods`
- `/api/taxes`
- `/api/api-tokens` (manage your own tokens via the API)

All resources support standard CRUD verbs: `GET` for collections and items, `POST` to create, `PATCH` to update, `DELETE` to remove. Amounts are in the currency's major units (see [Money format and API versions](#money-format-and-api-versions)), and the currency itself comes from the associated client.

## Troubleshooting

### `401 Unauthorized` on every request

The token is missing, mistyped, or has been revoked. Double-check the `X-API-TOKEN` header value against the original — leading or trailing whitespace and stray quote characters are common culprits when copying from terminals or password managers. If the token genuinely no longer works, generate a new one and update your integration.

### `429 Too Many Requests`

You've exceeded 300 requests per minute. Look at the `Retry-After` header in the response and wait at least that many seconds before retrying. For high-volume integrations, batch requests where possible, cache read-heavy responses, and stagger requests across the rate-limit window rather than firing them in tight loops.

### Authentication succeeds but the request is rejected with `403`

The token is valid but the authenticated user lacks permission for the action you requested. Verify the user owns the resource (or has the right role on the company that owns it) and that the token was generated while that company was active in the UI.

### Request history isn't recording your calls

Only **successful** authentication is recorded. If your requests are returning `401`, they won't appear in `View History` even if they reach the server. Make at least one request that returns `2xx` and refresh the history to confirm the token works.
