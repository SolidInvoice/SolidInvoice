# Hide app version in SaaS mode

## Problem

Dev builds are frequently deployed to the hosted (SaaS) environment to give hosted
customers early access to features. The app version is rendered in the "Powered by
SolidInvoice - `<version>`" footer, so emails and pages show things like
"Powered by SolidInvoice - 3.1.x-dev", which looks unprofessional to hosted customers.

For the hosted app the version should be omitted entirely. Self-hosted installs must
continue to show the version.

## Feature switch

SaaS mode is detected via the existing `saas_enabled` feature toggle
(`config/packages/toggler.php`):

```php
'saas_enabled' => '@=env("SOLIDINVOICE_PLATFORM") === \'saas\'',
```

This is a pure environment check — it needs no company context — so it is safe to
evaluate in web, CLI, and async (email-rendering) contexts. In PHP it is available
through `SolidWorx\Toggler\ToggleInterface::isActive('saas_enabled')`.

## Where the version is shown

The version reaches users only through the `app_version` Twig global, set in
`src/CoreBundle/Twig/Extension/GlobalExtension.php::getGlobals()`. It is consumed by
exactly four footer templates, each ending with `...</a> - {{ app_version }}`:

- `src/CoreBundle/Resources/views/Layout/default.html.twig`
- `src/CoreBundle/Resources/views/Layout/login.html.twig`
- `src/CoreBundle/Resources/views/Layout/error.html.twig`
- `src/CoreBundle/Resources/views/Layout/Email/components.html.twig`

No other template or PHP consumer reads the `app_version` global.

## Design

Single source of truth in `GlobalExtension`. Inject
`SolidWorx\Toggler\ToggleInterface` and make `app_version` `null` when `saas_enabled`
is active, otherwise the real version:

```php
'app_version' => $this->toggler->isActive('saas_enabled')
    ? null
    : SolidInvoiceCoreBundle::VERSION,
```

Guard the four footers so the separator and version render only when a version is
present (avoiding a dangling " - "):

```twig
...</a>{% if app_version %} - {{ app_version }}{% endif %}
```

### Why null-the-global rather than a separate flag

`app_version` has no consumer other than these four footers, and the desired behaviour
is "no version anywhere in hosted mode". Nulling the single global keeps one source of
truth and needs no new global. Templates already guard for a present value, so a future
footer added without the guard degrades gracefully to no separator.

## Out of scope

Machine-facing version exposure is intentionally left unchanged — these are not
user-facing notifications/messages and clients may depend on them:

- `McpBundle` well-known server card (`WellKnownServerCard`)
- API catalog / OpenAPI version
- Telemetry (`CoreBundle/Telemetry`)

## Testing

Unit test on `GlobalExtension::getGlobals()` with a stubbed `ToggleInterface`:

- `saas_enabled` active → `app_version` is `null`
- `saas_enabled` inactive → `app_version` equals `SolidInvoiceCoreBundle::VERSION`
