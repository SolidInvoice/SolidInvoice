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

- `list_resource`, `get_resource` (`mcp:read`): invoices, recurring invoices, quotes, clients, contacts, payments, payment methods and taxes.
- `list_workflow_transitions` (`mcp:read`): the status changes currently allowed on an invoice, quote or recurring invoice.
- `create_resource`, `update_resource`, `delete_resource` (`mcp:write`): clients, contacts and taxes.

Call `tools/list` after connecting for the full input schemas.

## Other machine-readable resources

- REST API (OpenAPI): `%base_url%/api/docs.json`
- Agent registration notes: `%base_url%/auth.md`
