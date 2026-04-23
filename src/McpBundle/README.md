# SolidInvoice MCP Server

Exposes SolidInvoice to AI agents (Claude Desktop, Cursor, Goose, mcp-inspector, etc.) over the **Model Context Protocol** using Streamable HTTP with OAuth2 authentication.

## What it gives AI agents

- **Read tools** (scope `mcp:read`): list/get invoices, quotes, clients, contacts, payments, taxes, recurring invoices; dashboard analytics; payment-method and tax-rate lookups; workflow-state inspection.
- **Write tools** (scope `mcp:write`): create/update/delete clients, contacts, taxes; apply workflow transitions on invoices, quotes, and recurring invoices; record offline payments; clone invoices/quotes; convert a quote to an invoice; add a contact to a client; send a manual invoice reminder.

Every tool call runs under **exactly one company** — bound at consent time, immutable afterwards, enforced by the Doctrine `CompanyFilter`. A token issued for company A cannot access company B's data, even if the agent requests it.

## Setup

1. **Install dependencies** (already in `composer.json`):
   - `symfony/mcp-bundle`
   - `league/oauth2-server`

2. **Generate signing keys** — RS256 JWT signing keys for access tokens:

   ```bash
   bin/console mcp:keys:generate
   ```

   Keys are written to `$SOLIDINVOICE_CONFIG_DIR/oauth/` (persistent config directory,
   survives redeployments). The FrankenPHP launcher runs this command automatically on
   startup — deployments using `solidinvoice run` don't need this step.

   The command uses OpenSSL's CSPRNG (`OPENSSL_KEYTYPE_RSA` / `/dev/urandom`) for key
   material. The `SOLIDINVOICE_APP_SECRET` is separately used as the encryption key
   for OAuth auth-code payloads via `league/oauth2-server`'s `AuthorizationServer` —
   not for the RSA keys themselves.

3. **Run the migration**:

   ```bash
   bin/console doctrine:migrations:migrate
   ```

   Creates `mcp_oauth_client`, `mcp_oauth_auth_code`, `mcp_access_token`, `mcp_refresh_token`, `mcp_consent_grant`.

4. **Start the app** (dev):

   ```bash
   symfony serve
   ```

## Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/.well-known/oauth-authorization-server` | RFC 8414 metadata |
| `GET` | `/.well-known/oauth-protected-resource` | RFC 9728 metadata |
| `POST` | `/oauth/register` | RFC 7591 Dynamic Client Registration (rate-limited) |
| `GET` | `/oauth/authorize` | Consent screen (requires session login) |
| `POST` | `/oauth/authorize` | Processes consent submission |
| `POST` | `/oauth/token` | Token endpoint (auth_code + refresh_token grants) |
| `POST` | `/oauth/revoke` | Token revocation (RFC 7009) |
| `GET`/`POST` | `/_mcp` | MCP Streamable HTTP endpoint |
| `GET` | `/profile/connected-apps` | User-facing list of connected agents |
| `POST` | `/profile/connected-apps/{id}/revoke` | Revoke a connected agent |

## Authorization flow

```text
agent  ── discover ──►  /.well-known/oauth-authorization-server
agent  ── register ──►  /oauth/register           → client_id (+ secret if confidential)
agent  ── redirect user ──►  /oauth/authorize?response_type=code&client_id=...
                             &code_challenge=...&code_challenge_method=S256
                             &scope=mcp:read mcp:write

user signs in → SolidInvoice shows the consent page
  - multi-company users: pick which tenant this token binds to
  - scopes: Read always on; Write togglable (only if requested)
  - "Don't ask me again" remembers the grant for (client + user + company + scopes)

user approves → /oauth/authorize redirects to client's redirect_uri with ?code=...

agent  ── POST ──►  /oauth/token
                    grant_type=authorization_code&code=...&code_verifier=...

  → { access_token: eyJ..., refresh_token: abc..., token_type: Bearer, expires_in: 3600 }

agent  ── POST ──►  /_mcp (Authorization: Bearer eyJ...)
                    { jsonrpc: "2.0", method: "tools/call", params: { name: "list_invoices", arguments: {} } }
```

### Grants

- `authorization_code` — primary flow. PKCE (S256) mandatory for public clients.
- `refresh_token` — rotated on each use; old refresh token invalidated.

### Scopes

- `mcp:read` — read-only tools
- `mcp:write` — read + write tools (write implies read)

## Multi-tenancy guarantees

Three independent layers:

1. **Token binding (write-time)** — `McpAccessToken.company_id` is set at consent time from the user's choice, immutable.
2. **Filter activation (read-time)** — `McpOAuthAuthenticator` calls `CompanySelector::switchCompany()` on every authenticated MCP request, enabling `CompanyFilter`.
3. **Write-path overrides** — `create_resource` / `record_payment` / `add_contact` strip any client-supplied `company` / `company_id`, always pulling from the active company.

## Connecting from Claude Desktop / mcp-inspector

```bash
npx @modelcontextprotocol/inspector https://your-solidinvoice-host/_mcp
```

The inspector walks through DCR + OAuth flow automatically.

In Claude Desktop: add a remote MCP server pointing to `https://your-solidinvoice-host/_mcp`.

## Session storage

MCP sessions (per-connection state between JSON-RPC calls) can be persisted in
several backends. Pick the one that matches your deployment topology via env vars:

| Store       | How to enable                                              | Notes                                                               |
|-------------|------------------------------------------------------------|---------------------------------------------------------------------|
| `file`      | `SOLIDINVOICE_MCP_SESSION_STORE=file` (default)            | On-disk in `var/cache/<env>/mcp-sessions/`. Single-node deployments. |
| `memory`    | `SOLIDINVOICE_MCP_SESSION_STORE=memory`                    | In-process. Resets on every worker restart — dev only.               |
| `cache`     | `SOLIDINVOICE_MCP_SESSION_STORE=cache` + point `SOLIDINVOICE_MCP_SESSION_CACHE_POOL` at a Redis-backed PSR-6 pool | Multi-node deployments (Redis, Memcached, etc.).                    |
| `framework` | `SOLIDINVOICE_MCP_SESSION_STORE=framework`                 | Shares the app's Symfony session handler.                            |

Additional env vars:

- `SOLIDINVOICE_MCP_SESSION_PREFIX` — key prefix (default `mcp-`)
- `SOLIDINVOICE_MCP_SESSION_TTL` — session TTL in seconds (default `3600`)

## Audit logging

Every `/_mcp` request is logged to the `mcp` monolog channel with: `method`, `tool`, `status`, `latency_ms`, `user_id`, `company_id`, `scopes`, `access_token_id`, `ip`.

## Revoking access

Users can revoke any connected agent from `/profile/connected-apps`. Revocation:

- Marks every `McpAccessToken` for that client+user pair as revoked (downstream requests fail `401 invalid_token`).
- Marks linked `McpRefreshToken`s as revoked.
- Does NOT delete the `OAuthClient` (the same agent can re-consent later).
