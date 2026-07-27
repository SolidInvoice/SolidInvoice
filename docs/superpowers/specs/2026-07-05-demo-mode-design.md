# Demo Mode — Design Spec

**Date:** 2026-07-05 (revised 2026-07-06)
**Status:** Approved for planning
**Author:** Pierre du Plessis (with AI assistant)

> **REVISION 2026-07-06 — mode-based architecture (supersedes §2–§3.2 below).**
> The original design used separate booleans (`SOLIDINVOICE_DEMO` + `SOLIDINVOICE_PLATFORM==='saas'`)
> and a `DemoMode` service queried with `isEnabled()` at each restriction site. That is replaced by a
> **single `SOLIDINVOICE_MODE` enum** (`self-hosted` | `demo` | `saas`), one `ModeResolver` service, and a
> **`Capability` policy** so call sites ask *"is this capability allowed in the current mode?"* instead of
> *"are we in demo?"*. `SOLIDINVOICE_PLATFORM` is fully retired (all consumers migrated). The full revised
> architecture is in **§3A** below; where §2/§3.1/§3.2 conflict with §3A, §3A wins. The purpose (§1),
> restriction *set* (§3.4), reset tooling (§3.3), and UI (§3.5) are unchanged in intent — only the
> detection/gating mechanism changed.

## 3A. Mode-based architecture (revised)

### 3A.1 Single mode switch

`SOLIDINVOICE_MODE` env var, default `self-hosted`. Backed by a PHP backed enum:

```php
enum ApplicationMode: string {
    case SelfHosted = 'self-hosted';  // default
    case Demo       = 'demo';
    case Saas       = 'saas';
}
```

Mutual exclusion is now **structural** — the value is exactly one mode, so demo and saas can never both be
active. The old boot-time SaaS-vs-demo guard is therefore **removed**, replaced by validation that rejects an
unknown `SOLIDINVOICE_MODE` value (fail fast at boot). `SOLIDINVOICE_PLATFORM` is retired entirely.

### 3A.2 Env vars (revised §2)

| Env var | Purpose | Default |
|---|---|---|
| `SOLIDINVOICE_MODE` | Application mode: `self-hosted`, `demo`, or `saas` | `self-hosted` |
| `SOLIDINVOICE_DEMO_USERNAME` | Demo shared-account login (email); **required when mode=demo** | `''` |
| `SOLIDINVOICE_DEMO_PASSWORD` | Demo shared-account password; **required when mode=demo** | `''` |
| `SOLIDINVOICE_DEMO_SIGNUP_URL` | External "get your own account" CTA target (opt-in) | `''` |

`SOLIDINVOICE_DEMO` and `SOLIDINVOICE_PLATFORM` no longer exist. If `SOLIDINVOICE_MODE=demo` but username or
password is unset, the app fails fast (misconfiguration) rather than silently degrading to non-demo.

### 3A.3 ModeResolver + Capability policy

A single `SolidInvoice\CoreBundle\Mode\ModeResolver` service (CoreBundle), reading `SOLIDINVOICE_MODE`:

- `current(): ApplicationMode`
- `is(ApplicationMode $mode): bool` (+ convenience `isDemo()/isSaas()/isSelfHosted()`)
- `allows(Capability $capability): bool` — backed by a per-mode policy map
- demo parameter accessors: `demoUsername(): ?string`, `demoPassword(): ?string`, `demoSignupUrl(): ?string`
  (null when unset / not in demo)

```php
enum Capability {
    case UserRegistration;        // demo denies (registration + OAuth auto-registration)
    case RealEmailDelivery;       // demo denies (mailer forced to null transport)
    case RealNotificationDelivery;// demo denies (NotificationManager short-circuits)
    case OnlinePaymentCapture;    // demo denies (Payum online capture blocked)
    case CredentialChange;        // demo denies (shared-account email/password locked)
}
```

Policy: **self-hosted allows all; saas allows all** (SaaS restrictions are handled separately by the existing
plan `FeatureGate` / subscription layer, not here); **demo denies the capabilities above**. Restriction call
sites become `if (! $this->modeResolver->allows(Capability::RealEmailDelivery)) { … }`.

**Positioning against the two pre-existing systems (kept distinct):**
- **`solidworx/toggler`** = "is a capability *wired/configured*" (env presence). `saas_enabled` is **redefined**
  as `SOLIDINVOICE_MODE === 'saas'`.
- **`FeatureGate`** (SaasBundle) = "does this tenant's *paid plan* include this feature" — unchanged, saas-only.
- **`ModeResolver::allows(Capability)`** = "is this action *permitted in the current run mode*" — the new,
  demo-driven layer.

Presentation concerns (DEMO watermark, login banner + prefill, in-app banner, config-form warning alerts) key
off mode **identity** (`ModeResolver::isDemo()` / a `is_demo()` Twig helper), not the capability policy — they
are display, not permission gates.

### 3A.4 Runtime vs container-build-time split (A2 migration)

`SOLIDINVOICE_PLATFORM`/`saas_enabled` are used in two kinds of place; the migration treats them differently:

- **Runtime service consumers** (`ApiAccessVoter`, `McpAccessVoter`, `SubscriptionVoter`, `WithinPlanClientLimitValidator`,
  `WithinPlanInvoiceLimitValidator`, `VerifiedUserChecker`, `SubscriptionService`, `CreateCompany`, and the
  `toggle('saas_enabled')` template usages) → migrate to `ModeResolver` (or an `is_saas()` Twig helper).
- **Container-build-time env reads** (`config/bundles.php`, `src/Kernel.php`, `config/services_test.php`,
  `src/CoreBundle/Resources/config/services/services.php`) run before services exist → read `SOLIDINVOICE_MODE`
  from `$_ENV`/`$_SERVER` directly (`=== 'saas'` / `=== 'demo'`). These cannot use the resolver service.

Twig helpers exposed: `app_mode()` (enum value string), `is_demo()`, `is_saas()`, plus the demo-parameter
accessors `demo_username()`, `demo_password()`, `demo_signup_url()`. The old `demo_enabled()` Twig function and
`saas_enabled` toggler flag are removed/redefined accordingly.


## 1. Purpose

Add a **demo mode** to SolidInvoice: a self-hosted instance that boots with pre-seeded
data so anonymous visitors can log in with a shared account and explore the full product.
The instance resets on a schedule (e.g. hourly) to a pristine state.

Guiding principle: **everything stays visible and appears to work; real-world
side-effects and abuse vectors are neutralized at the lowest possible level, with
minimal UI changes.** A visitor should get a faithful feel for the product without being
able to (a) send real emails/notifications, (b) move real money, (c) lock other visitors
out of the shared account, or (d) otherwise abuse the shared instance.

This is a marketing surface (a shopfront for the paid/hosted product). That intent is
**never surfaced in the UI or code comments** — the demo simply presents the app and an
optional "get your own account" call-to-action.

Non-goals (explicitly deferred, not in this work):
- Rate limiting of any kind (general or demo-specific), including closing the MCP
  rate-limit gap. Deferred to a later effort.

## 2. Configuration (env vars)

| Env var | Purpose | Default |
|---|---|---|
| `SOLIDINVOICE_DEMO` | Master switch for demo mode | `0` |
| `SOLIDINVOICE_DEMO_USERNAME` | Login identifier (email) for the shared demo account | `''` |
| `SOLIDINVOICE_DEMO_PASSWORD` | Password for the shared demo account | `''` |
| `SOLIDINVOICE_DEMO_SIGNUP_URL` | External URL for the "get your own account" CTA | `''` |

Demo mode is **enabled only when** `SOLIDINVOICE_DEMO` is truthy **and** both
`SOLIDINVOICE_DEMO_USERNAME` and `SOLIDINVOICE_DEMO_PASSWORD` are set **and** the instance
is not in SaaS mode (`SOLIDINVOICE_PLATFORM !== 'saas'`). If the credentials are missing,
demo mode stays off (fail-safe).

Env defaults are registered in `config/services.php` alongside the existing
`SOLIDINVOICE_*` defaults.

## 3. Architecture

Two existing systems inform the design (do not conflate them):

- **`solidworx/toggler`** — env-driven boolean toggles registered in
  `config/packages/toggler.php` (e.g. `allow_registration`, `saas_enabled`). This is where
  the demo flag lives.
- **Platform `FeatureGate`** — plan-based SaaS feature gating. Resolves to `NoopFeatureGate`
  (everything on) when not in SaaS mode. Demo relies on this — no per-feature code needed.

No new bundle. Demo code is scattered into the bundles that own each concern, with a single
shared detection service everything keys off.

### 3.1 Detection (Phase 1)

**Toggle** — add to `config/packages/toggler.php`:

```php
'demo_enabled' => '@=env("SOLIDINVOICE_DEMO") == true
    && env("SOLIDINVOICE_DEMO_USERNAME") !== null
    && env("SOLIDINVOICE_DEMO_PASSWORD") !== null
    && env("SOLIDINVOICE_PLATFORM") !== "saas"',
```

**Single source of truth** — a `DemoMode` service in **CoreBundle**, wrapping
`SolidWorx\Toggler\ToggleInterface`:

- `isEnabled(): bool` — delegates to `isActive('demo_enabled')`.
- `username(): ?string`, `password(): ?string`, `signupUrl(): ?string` — read the env vars.

Every consumer (listeners, forms, templates, the reset command) asks `DemoMode` (or the
`demo_enabled` toggle / a `demo_enabled()` Twig function) — never re-reads env directly.
This keeps the decision in exactly one place.

**Twig** — a `demo_enabled()` Twig function (and helpers for the demo username/password/
signup URL) exposed from a CoreBundle Twig extension, for the login/banner/watermark templates.

### 3.2 Mutual exclusion with SaaS

Belt and braces:

- `config/bundles.php` already loads the SaaS bundles only when
  `SOLIDINVOICE_PLATFORM === 'saas'`. Add a guard: if `SOLIDINVOICE_DEMO` is truthy **and**
  platform is `saas`, throw a clear exception at boot ("demo and saas modes are mutually
  exclusive") rather than silently choosing one.
- Because the SaaS bundles never load in demo, all SaaS gating resolves to
  `NoopFeatureGate`: every self-hosted feature is available, no SaaS-only feature is. No
  per-feature work required.

### 3.3 Reset tooling (Phase 2)

New console command **`solidinvoice:demo:reset`** (CoreBundle), extending the Platform
`Command` base class.

**Availability guard:** override `isEnabled(): bool` on the command to return
`DemoMode::isEnabled()`. Outside demo mode the command is not registered/visible at all —
it cannot be run, from cron or explicitly. (Same mechanism `app:install` uses once installed.)

**Steps (full teardown):**
1. Drop the schema (full database, via Doctrine SchemaTool / DBAL).
2. Recreate from migrations (reuse the InstallBundle `Migration` runner — new migrations
   are picked up automatically on redeploy).
3. Create the demo `User` from `SOLIDINVOICE_DEMO_USERNAME` / `_PASSWORD` (hashed),
   `enabled + verified`, role `ROLE_SUPER_ADMIN`.
4. Create a demo `Company` and associate the user (reusing the existing company-creation
   pattern from `OnboardingManager` / `CompanyRepository`); switch the company filter to it
   via `CompanySelector`.
5. Run the existing `DummyDataLoader` pipeline against that company (the same pipeline
   `solidinvoice:dummy-data:load` uses).

**Concurrency:** wrap the run in a Symfony Lock so overlapping cron invocations can't
collide mid-teardown.

**Cron:** documented as an infra-level cron entry invoking the command on the desired
interval (e.g. hourly). No in-app scheduler code.

### 3.4 Restrictions & neutralization (Phase 3)

Each is a thin hook keyed off `DemoMode::isEnabled()`. Config screens stay **visible and
editable**; only the real-world effect is removed. Sensitive config forms additionally show
a warning alert.

| Concern | Where | Behavior in demo |
|---|---|---|
| **Registration** | `UserBundle/Action/Register.php` (+ OAuth auto-register in `OAuthAuthenticator`) | Force-denied (404) when demo, regardless of `allow_registration`. Invited users still bypass (invitations work; their email is black-holed). |
| **Email delivery** | `MailerBundle/Factory/MailerConfigFactory` decorator | In demo, skip the DB provider config entirely and force the env DSN (`null://null`). Sending invoices/quotes/invitations *looks* normal; nothing leaves. |
| **SMTP / transport config** | `SettingsBundle` mail transport form | Stays editable; shows a demo warning alert. Backend ignores whatever is saved (mailer forced to `null://null` above), so a real server can never be reached. |
| **Notifications** (Slack/SMS/Telegram/etc.) | `NotificationBundle/Notification/NotificationManager::sendNotification()` | Short-circuit: in demo, return before dispatching so nothing reaches third parties. Subscription/config UI stays visible (with warning alert). |
| **Payments** | Payment capture / Payum execute path | Online-gateway capture disabled in demo (no live charges). Offline/manual methods still work so the flow is demoable. Gateway config stays visible (with warning alert). |
| **Credential changes** | `UserBundle` profile/account edit | Email + password fields disabled and server-side blocked in demo, so nobody can change the shared login and lock others out. |
| **User management** | invite / create / delete / disable / role changes | Left working as normal (invitation emails are black-holed). Only the shared account's own credentials are protected (row above). |
| **SaaS features** | n/a | SaaS bundles cannot load in demo → `NoopFeatureGate` → all self-hosted features on, no SaaS features. No per-feature code. |

**Demo warning alert** — a small reusable partial ("You are in a demo instance. Do not enter
real or sensitive information (passwords, API keys, card details); these settings will not
take effect."), included at the top of the SMTP, notification-integration, and payment-gateway
config forms when `demo_enabled()`.

**Note on API/MCP:** no special "you are in demo" errors on send actions. Sending an
invoice/quote/invitation via UI, API, or MCP appears to succeed; the email is black-holed and
no payment/notification actually fires. This supersedes the earlier idea of making API sends
return an error.

### 3.5 Watermark & in-app UI (Phase 4)

- **PDF watermark (unremovable).** In the `InvoiceBundle` / `QuoteBundle` PDF Twig templates,
  emit `<watermarktext content="DEMO">` **unconditionally** when `demo_enabled()` — not gated
  on the `invoice/watermark` setting or the `custom_branding` feature, so it cannot be turned
  off. Applies to invoices, quotes, and recurring invoices.
- **Printer-friendly HTML view.** Add a fixed `DEMO` watermark overlay (CSS) to the print-view
  templates when in demo.
- **Login page** (`UserBundle/Resources/views/Security/login.html.twig`). In demo: a banner
  showing the demo username + password, and the form pre-filled — seed `last_username` in
  `UserBundle/Action/Security/Login.php` and render the password field with the demo value.
- **In-app demo banner.** A slim persistent banner in the base layout (gated on
  `demo_enabled()`) noting it's a demo with limited functionality, plus the signup CTA linking
  to `SOLIDINVOICE_DEMO_SIGNUP_URL` when that var is set (CTA omitted when unset).

## 4. Testing

- **Detection:** `DemoMode` returns false unless all conditions hold (master switch,
  username, password, non-SaaS); the `demo_enabled` toggle mirrors it. Boot-time guard throws
  when demo + saas are both requested.
- **Reset command:** `isEnabled()` is false (command absent) outside demo; the happy path
  seeds user + company + dummy data on a full teardown. Lock prevents concurrent runs.
- **Restrictions:** registration 404s in demo; mailer resolves to `null://null` even with a DB
  provider configured; `NotificationManager` does not dispatch; online payment capture is
  blocked; credential-change fields are blocked server-side.
- **UI/watermark:** PDF output contains the `DEMO` watermark regardless of settings; login
  page renders banner + prefilled credentials in demo and not otherwise.

Follow existing test conventions (functional tests under each bundle's `Tests/Functional`,
Foundry factories for fixtures). Run `bin/ecs check --fix && bin/phpstan analyse && bin/phpunit`.

## 5. Implementation phases

1. **Foundation** — `demo_enabled` toggle, `DemoMode` service, `demo_enabled()` Twig
   function, SaaS mutual-exclusion boot guard, env defaults.
2. **Reset tooling** — `solidinvoice:demo:reset` command (guarded, full teardown, seed,
   lock) + cron docs.
3. **Restrictions & neutralization** — registration off, mailer→null, SMTP/notification/
   payment-gateway neutralization + warning alert, notification short-circuit, online-payment
   block, credential-change lock.
4. **Watermark & UI** — DEMO PDF/print watermark, login banner + prefill, in-app demo banner
   + signup CTA.

Each phase is independently reviewable and testable.
