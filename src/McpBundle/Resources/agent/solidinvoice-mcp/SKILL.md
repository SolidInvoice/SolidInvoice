---
name: solidinvoice-mcp
description: Connect to a SolidInvoice account over MCP to read and manage invoices, quotes, clients, contacts, payments and taxes.
---

# SolidInvoice MCP

SolidInvoice exposes an MCP server (Streamable HTTP) at `%base_url%/_mcp`.

## Connect

1. Discover the authorization server: `GET %base_url%/.well-known/oauth-protected-resource/_mcp`.
2. Register a client with RFC 7591 Dynamic Client Registration: `POST %base_url%/oauth/register`.
3. Send the user to `%base_url%/oauth/authorize` (authorization code + PKCE S256). The user signs in, picks the company the token is bound to, and approves the scopes.
4. Exchange the code at `%base_url%/oauth/token`, then call `%base_url%/_mcp` with `Authorization: Bearer <access_token>`.

Scopes: `mcp:read` (read-only tools) and `mcp:write` (adds write tools).

Every token is bound to exactly one company. You cannot reach another company's data with it.

## Tools

- `get_*` and `list_*` (`mcp:read`): one tool pair per resource, e.g. `get_invoice` / `list_invoices`, for invoices, recurring invoices, quotes, clients, contacts, payments, payment methods and tax rates. Lists are paginated (`page`, `items_per_page` up to 100).
- `list_workflow_transitions` (`mcp:read`): the status changes currently allowed on an invoice, quote or recurring invoice.
- `create_*`, `update_*`, `delete_*` (`mcp:write`): clients, contacts and tax rates.

IDs are returned in base58; any ULID form is accepted as input. Money is a decimal string in the document's currency, at that currency's precision (`"12.50"` USD, `"1500"` JPY, `"1.234"` BHD). JSON numbers are rejected for money inputs. Totals that can span currencies come back as a list of `{currency, amount}`. Lists return `{results, page, items_per_page, total}`.

`tools/list` is visible without signing in; calling a tool needs a token. It also gives the full input schemas and each tool's read-only/destructive/open-world hints.

## Other machine-readable resources

- REST API (OpenAPI): `%base_url%/api/docs.json`
- Agent registration notes: `%base_url%/auth.md`
