# auth.md

You are an agent. SolidInvoice authenticates agents with standard OAuth 2.1: dynamic client registration, then the authorization code flow with PKCE. A human SolidInvoice user must sign in and approve access. There is no anonymous or headless registration.

## Step 1 — Discover

- Protected resource metadata (RFC 9728): `GET %base_url%/.well-known/oauth-protected-resource/_mcp`
- Authorization server metadata (RFC 8414): `GET %base_url%/.well-known/oauth-authorization-server`

An unauthenticated request to `%base_url%/_mcp` returns `401` with a `WWW-Authenticate: Bearer resource_metadata="..."` header that points at the same metadata.

## Step 2 — Register

```http
POST %base_url%/oauth/register
Content-Type: application/json

{
  "client_name": "My Agent",
  "redirect_uris": ["http://127.0.0.1:33418/callback"],
  "grant_types": ["authorization_code", "refresh_token"],
  "token_endpoint_auth_method": "none"
}
```

The response contains your `client_id` (and a `client_secret` for confidential clients).

## Step 3 — Ask the user to authorize

Open `%base_url%/oauth/authorize?response_type=code&client_id=...&redirect_uri=...&code_challenge=...&code_challenge_method=S256&scope=mcp:read%20mcp:write&state=...` in the user's browser.

The user signs in, picks the company this token is bound to, and approves the scopes. SolidInvoice then redirects to your `redirect_uri` with `?code=...`.

## Step 4 — Exchange the code

```http
POST %base_url%/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&code=...&redirect_uri=...&client_id=...&code_verifier=...
```

You receive an `access_token` (short-lived; one hour by default) and a `refresh_token`. Refresh tokens rotate on every use.

## Step 5 — Use the token

Send `Authorization: Bearer <access_token>` to `%base_url%/_mcp`.

## Scopes

- `mcp:read`: read-only tools
- `mcp:write`: read and write tools

## Revocation

- Agent side: `POST %base_url%/oauth/revoke` (RFC 7009).
- User side: the user can revoke your access at any time from their profile. After that, requests fail with `401 invalid_token`; start again from Step 3.
