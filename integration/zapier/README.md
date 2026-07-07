# SolidInvoice Zapier Integration

CLI-managed Zapier integration for SolidInvoice 3.0+. Connects clients, invoices, quotes, payments, and recurring invoices to 8,000+ Zapier-enabled apps.

- **Zapier app id:** `18306` (unique slug `App18306`)
- **Integration version:** `package.json` `version` field. Decoupled from SolidInvoice's semver.
- **SolidInvoice compatibility:** 3.0+ only. Uses API Platform 4.0 Hydra/JSON-LD endpoints.

## Layout

```
integration/zapier/
  authentication.js     # X-API-TOKEN + instance URL auth + live test
  middleware.js         # injects auth header, translates Hydra errors
  constants.js          # enum values, workflow transitions, shared choices
  index.js              # wires triggers / creates / searches / searchOrCreates
  triggers/             # polling triggers (new/updated/status-change per resource)
  creates/              # create, update, transition, send, convert actions
  searches/             # lookup actions (by id, filter-based)
  searches_or_creates/  # find-or-create wrappers
  utils/                # pagination, money helpers, field schemas, sample payloads
  test/                 # jest + nock unit tests
```

## Local development

Prerequisite: Node 22+, Zapier CLI (`npm i -g zapier-platform-cli` or Homebrew), and `zapier login`.

```bash
cd integration/zapier
npm install

# unit tests
npm test

# schema validation
npx zapier validate

# invoke a trigger live against a local SolidInvoice (edit .env first)
cp .env.example .env   # fill in your token + URL
npx zapier invoke trigger new_invoice
```

### `.env`

Never commit `.env`. Required keys for live invocation:

```
API_TOKEN=your_solidinvoice_token
SERVER_URL=http://localhost
```

Generate an API token at `{SERVER_URL}/profile/api` → "Create Token".

## Deployment

CI lives in `.github/workflows/zapier.yml`. Summary:

| Event | Behaviour |
|---|---|
| PR touching `integration/zapier/**` | Validate + test |
| Push to `main` / `3.0.x` | Validate + test + `zapier push` (uploads current `package.json` version, not promoted) |
| `workflow_dispatch` `action=push` | Same as push above (manual trigger) |
| `workflow_dispatch` `action=promote`, `version=x.y.z` | Validate + test + `zapier push` + `zapier promote x.y.z` |

Promotion is the step that makes a version live for new users. It is always manual.

### Release checklist

1. Bump `package.json` `version`.
2. Update `CHANGELOG.md`.
3. Open PR → CI validates. Merge once green.
4. Push build lands automatically. Verify in the Zapier Editor against a staging company.
5. `gh workflow run zapier.yml -f action=promote -f version=<x.y.z>` once confident.
6. For minor/patch bumps, optionally `zapier migrate <old> <new> 100` after the 14-day deprecation notice expires. Major bumps force users to reconnect.

## Versioning rules

- **Patch** — bug fixes in perform functions, sample data updates, error message tweaks.
- **Minor** — added trigger/action/search, new optional input/output fields. Migratable via `zapier migrate`.
- **Major** — field rename/removal, auth change, polling → webhook swap on an existing trigger. Users reconnect.

## Supported resources (v1)

Every SolidInvoice API Platform resource has at least one trigger, create, or search:

- **Client** — new / updated triggers, create action, find / find-or-create search
- **Contact** — new trigger, create action, find-by-email search
- **Address** — create action
- **Invoice** — new / updated / status-changed / paid / overdue triggers, create / update / transition / send actions, find search
- **Quote** — new / updated / accepted / declined triggers, create / update / transition / convert-to-invoice actions, find search
- **Payment** — new / completed triggers, record action, find search
- **Recurring Invoice** — new trigger
- **Tax Rate** — new trigger, create action

All triggers are polling-only. REST Hooks are deferred until SolidInvoice ships an outbound webhook bundle.

## Authentication notes

- Custom auth with two fields: `server_url` (instance URL, default `https://solidinvoice.app`) and `api_token` (X-API-TOKEN value).
- Tokens are scoped to a single company. For multi-company deployments, users create one Zapier connection per company.
- Field keys preserved from the UI-builder 1.0.0 version for backward compatibility with existing Zaps.

## Troubleshooting

- `zapier validate` fails with a schema error → check any trigger/create has `key`, `noun`, `display.{label,description}`, `operation.perform`.
- `zapier push` fails with "version already promoted" → bump `package.json` version.
- Live calls 401 → API token is rotated/expired. Zapier prompts reconnect via the `RefreshAuthError` from `middleware.js`.
- Transition errors (422) → the target status transition isn't valid from the current state. See `constants.js` for the canonical list.

## Follow-ups (not in v1)

- Outbound webhook bundle in SolidInvoice → unlocks REST Hook triggers.
- OAuth2 auth → major bump when API supports it.
- Publishing to the Zapier public marketplace → separate effort after private beta and ≥10 active Zaps.
