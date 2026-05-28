3.0.0
=====

Billing template editor
-----------------------

* The `/settings` route now requires the `ROLE_ADMIN` role. Single-admin
  installations are unaffected, but multi-user installs where regular users
  previously accessed `/settings` will need to either grant `ROLE_ADMIN` to
  the relevant accounts or relax the new rule in
  `config/packages/security.php`.

* Billing templates (HTML, PDF, email) for invoices and quotes are now
  managed in a dedicated `billing_templates` table rather than being
  baked into the @SolidInvoiceInvoice/... and @SolidInvoiceQuote/...
  paths. Migration `Version30000_10` creates the table; on first login
  for every existing company `CoreBundle\\Company\\BillingTemplateInitializer`
  backfills the six seeded `Default` rows (invoice/html, invoice/pdf,
  invoice/email, quote/html, quote/pdf, quote/email). New custom rows
  can be created from **Settings → Billing Templates**.

* Custom templates are rendered through a **Twig sandbox**. The
  whitelist policy lives in
  `SolidInvoice\\CoreBundle\\Templating\\BillingTemplateResolver::createSecurityPolicy()`.

  * `constant`, `dump`, `include` and the generic `setting()` function
    are not callable inside user templates. A narrow
    `template_setting('whitelisted-key')` / `template_address(address)`
    pair is exposed instead — see
    `SolidInvoice\\CoreBundle\\Twig\\Extension\\TemplateSettingsExtension::ALLOWED_SETTINGS`
    for the list of safe keys.
  * The `raw` filter is rejected; templates are HTML-escaped by default
    and no built-in default uses `raw`.
  * Object access is whitelist-driven per class; any method or property
    not listed in the resolver's `createSecurityPolicy()` returns a
    `SecurityNotAllowed*Error` at render time. The editor's preview
    surfaces these errors inline.

* `SolidInvoice\\CoreBundle\\Twig\\Extension\\FileExtension::file()` now
  uses `realpath()` with a `/public/` boundary check, so
  `{{ file('../../.env') }}` returns an empty string instead of file
  contents.

* `Company::$currency` is now private with `getCurrency()` /
  `setCurrency()` accessors. Update any extension/plugin code that still
  pokes the property directly.

* `BillingTemplateResolver` is intentionally not marked `final` so
  Mockery can produce test doubles, but its API is otherwise stable.
  Listener/action signatures changed: PDF listeners and the public
  `View` actions now take a `BillingTemplateResolver` argument; any
  decorators or custom test setups need updating.

2.3.17
======

* API tokens are now stored as HMAC-SHA256 hashes (keyed by `SOLIDINVOICE_APP_SECRET`)
  instead of plaintext. The `Version20317` migration re-hashes all existing tokens
  in place, so previously issued tokens continue to work without user action.
* Existing tokens are no longer recoverable from the database or visible in the UI.
  After upgrading, the management page only lists token names; the value itself is
  shown exactly once at creation time and must be copied immediately.
* Rotating `SOLIDINVOICE_APP_SECRET` now invalidates all API tokens (previously it
  only invalidated sessions). After rotating the secret, users must generate new
  API tokens.

2.0.0
=====

* `SolidInvoice\NotificationBundle\Notification\ChainedNotificationInterface::addNotifications` and `SolidInvoice\NotificationBundle\Notification\ChainedNotification::addNotifications` has been renamed to `addNotification`
