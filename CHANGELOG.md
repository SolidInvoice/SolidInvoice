Changelog
=========

3.2.0 / Unreleased
-------------------

### Changes

* Every piece of text in the app, emails, PDFs and installer now comes from the translation files, so all of it can be translated. This covers payment reminder emails, notification and email subjects, list columns and filters, statuses, the installer, plan upgrade prompts and form validation messages
* Dates show month and day names in the user's language, and counts use the right singular or plural form (for example "1 day overdue" instead of "1 days overdue")
* Dates on invoice and quote emails no longer include a meaningless time, and short dates no longer pad the day ("Sep 1, 2021" instead of "Sep 01, 2021")
* Creating a company now asks for its country, with a searchable list that shows each country's flag. Companies in the EU, the UK, Switzerland, Monaco, the Isle of Man, South Africa and Australia start with their country's VAT or GST rates already set up
* AI agents can discover SolidInvoice on their own: the app now serves `/auth.md` with sign-in steps for agents, an agent skill for connecting over MCP, and an `/.well-known/ai-catalog.json` listing the MCP server and REST API

### Fixes

* MCP clients that connect without a token now receive a `WWW-Authenticate` header pointing them to the OAuth metadata, so they can start sign-in automatically
* The installer no longer stops with a JavaScript error when installation finishes
* Deleting a custom field whose name contains an apostrophe now shows its confirmation prompt
* Apostrophes in plain-text emails no longer show up as `&#039;`


3.1.3 / Unreleased
------------------

### Fixes

* Trimmed internal payment method fields from API payment responses

3.1.2 / 2026-10-21
-------------------

### Changes

* Database migrations no longer run from a web request. The built-in server runs them once at startup, before it serves anything, and the Helm chart runs them as a pre-upgrade job. Other setups should run `bin/console doctrine:migrations:migrate` after deploying
* Invoices and quotes issued before 3.1.0 now have their currency frozen too, so changing a client's or company's currency no longer changes them. This resolves the 3.1.0 known issue
* An invoice or quote that goes back to Draft follows its client's currency again. An invoice that already has a payment keeps its currency
* The "Powered by SolidInvoice" line in emails, invoice and quote PDFs, and the client invoice page now links to a page about SolidInvoice

### Fixes

* Upgrades actually run each migration's data changes. Before, they were only marked as done, which could lose data (such as VAT numbers) and made upgrades on MySQL, MariaDB and PostgreSQL fail with a server error on every page
* Upgrading on PostgreSQL no longer fails with an encoding error, and invoice lines no longer lose the ability to belong to a recurring invoice there
* Recurring invoices keep their contacts when upgrading. The database table is now renamed instead of being at risk of being dropped and recreated empty
* Password reset requests and pending user invitations keep their data when upgrading
* The `messenger_messages` table is now actually created by migrations. The 3.1.0 fix for this did nothing
* Remaining differences between the database and the application's expected schema (column types, nullability, indexes) are now fixed on upgrade
* Recording a payment through the API or the MCP tools now checks it against the invoice's own currency, not the client's. Correct payments were rejected after a client changed currency
* Notifications, API keys, Connected apps and Data export are back in the profile menu
* The API token history table header is bold again, and info boxes show a hover colour again
* The search bar in the top header has its border and background again, and its results dropdown no longer shows through onto the page behind it. The company page and subscription pages had the same missing colours and are fixed too

3.1.1 / 2026-10-15
-------------------

### Changes

* Application logs now show up in the server output. Production logs everything from `info` up (set `SOLIDINVOICE_LOG_LEVEL`, e.g. to `warning`, to change that), web requests log through the web server's own logger, and log lines from background workers and console commands keep their level instead of all showing as `info`

### Fixes

* A company's own mail delivery settings now apply only to that company's emails. Emails sent before a company was selected (such as the two-factor login code) could go out through another company's mail provider, and long-running workers kept using the first company's provider for every company
* "Resend code" on the two-factor login page works again. Users with more than one company were redirected to company selection and back without a new code being sent
* The two-factor login code email is now sent from `SOLIDINVOICE_MAILER_SENDER`. It used to pick up another company's sender address, or the user's own address, so mail providers often rejected it and the code never arrived
* `SOLIDINVOICE_APP_SECRET` is used again. 3.1.0 ignored it and used a value that changed every time the cache was rebuilt, so API tokens, "remember me" logins and signed links stopped working after each deploy or restart. API tokens created while running 3.1.0 need to be regenerated
* The built-in web server now caches only content-hashed static files for a year. Other static files are revalidated after an hour, so a release can no longer be hidden behind a long-cached copy of a file whose name did not change
* Setting up an authenticator app no longer fails when the code it shows starts with 0
* Authenticator app codes now use the standard 30-second period instead of 20 seconds, so Google Authenticator and Microsoft Authenticator codes are accepted reliably. If you set up 2FA with an app that honours the period from the QR code (such as 1Password, Authy or Bitwarden), remove the entry and scan the QR code again under **Profile → Two-Factor Authentication**
* Release builds ship production assets again, with content-hashed file names. 3.1.0 shipped a development build whose file names never change between releases, so browsers and CDNs kept serving the old 3.0 CSS and JavaScript: buttons showed the default blue instead of the brand green and the user menu dropdown stopped opening. If you put a CDN in front of SolidInvoice, purge its cache once after upgrading

3.1.0 / 2026-10-15
-------------------

### Highlights

* Invoice, recurring invoice and quote lines can now be named separately from their description, and keep an explicit order
* Invoices, recurring invoices and clients can have payment terms (Net 15, Net 30, Net 60, due on receipt, or a custom date), with a configurable company-wide default
* Issued invoices and quotes now freeze their currency going forward, so changing a client's or company's default currency no longer retroactively restates documents issued *after* upgrading. Documents issued before the upgrade are not covered yet — see "Known issues" below

### Changes

* Getting started is now a single step. New users enter their company name and currency, then go straight to the invoice editor to add their first client and send their first invoice. The separate client, invoice and "you're all set" screens are gone; the first invoice used to be saved without a recipient, so it could not be sent
* The dashboard setup checklist now starts with "Create Your Company" already ticked off
* "Send to client" is now the main button when creating or editing an invoice, and on a draft invoice's page. "Save as Draft" and "Publish without sending" are still one click away
* Until you confirm your email address, the Send button stays visible but disabled, with the reason next to it and a link to get a new confirmation email
* Invoice, quote and reminder emails now carry a Reply-To of your company's email address (or your own, if the company has none), so a client's reply reaches you instead of the no-reply sender
* A published invoice now has a "Copy client link" button that copies the link to the invoice's public page
* Saving an invoice or quote with no contact selected now says to choose a contact or add one with an email address
* Before you confirm your email address, an invoice's page offers "Email me a test copy". It sends the invoice, with its PDF, to your own inbox only, along with a link that confirms your address and brings you back to the invoice, ready to send. Limited to 3 per account
* SolidInvoice Cloud trial emails now follow what you do instead of arriving every day and a half. The welcome is sent at signup; a "your first invoice takes 2 minutes" nudge goes out after a day with nothing sent; sending your first invoice brings the "now let them pay by card" email; and four days before the trial ends you get a recap of your usage with what you keep on Free. The logo, recurring billing, feedback, add-a-client and last-chance offer emails have been dropped
* Trial users who have not confirmed their email get a reminder after one day and again after three, and those reminders carry the confirmation link. Other trial emails are not sent to an unconfirmed address, so they cannot bounce
* With a card on file, the trial-ending email now goes out too, and says on which date the card will be charged

### Fixes

* Invoices generated from a recurring invoice are now emailed to their recipients. They were created and published, but no email went out
* A draft invoice, such as the one the onboarding wizard creates, no longer stops the "send your first invoice" email

3.1.1 / 2026-10-15
-------------------

### Changes

* Application logs now show up in the server output. Production logs everything from `info` up (set `SOLIDINVOICE_LOG_LEVEL`, e.g. to `warning`, to change that), web requests log through the web server's own logger, and log lines from background workers and console commands keep their level instead of all showing as `info`

### Fixes

* A company's own mail delivery settings now apply only to that company's emails. Emails sent before a company was selected (such as the two-factor login code) could go out through another company's mail provider, and long-running workers kept using the first company's provider for every company
* "Resend code" on the two-factor login page works again. Users with more than one company were redirected to company selection and back without a new code being sent
* The two-factor login code email is now sent from `SOLIDINVOICE_MAILER_SENDER`. It used to pick up another company's sender address, or the user's own address, so mail providers often rejected it and the code never arrived
* `SOLIDINVOICE_APP_SECRET` is used again. 3.1.0 ignored it and used a value that changed every time the cache was rebuilt, so API tokens, "remember me" logins and signed links stopped working after each deploy or restart. API tokens created while running 3.1.0 need to be regenerated
* The built-in web server now caches only content-hashed static files for a year. Other static files are revalidated after an hour, so a release can no longer be hidden behind a long-cached copy of a file whose name did not change
* Setting up an authenticator app no longer fails when the code it shows starts with 0
* Authenticator app codes now use the standard 30-second period instead of 20 seconds, so Google Authenticator and Microsoft Authenticator codes are accepted reliably. If you set up 2FA with an app that honours the period from the QR code (such as 1Password, Authy or Bitwarden), remove the entry and scan the QR code again under **Profile → Two-Factor Authentication**
* Release builds ship production assets again, with content-hashed file names. 3.1.0 shipped a development build whose file names never change between releases, so browsers and CDNs kept serving the old 3.0 CSS and JavaScript: buttons showed the default blue instead of the brand green and the user menu dropdown stopped opening. If you put a CDN in front of SolidInvoice, purge its cache once after upgrading

3.1.0 / 2026-10-15
-------------------

### Changes

* A "Share feedback" link now sits in the user menu and the app footer, opening an anonymous product feedback form. SolidInvoice Cloud trials also get one feedback email partway through the trial
* API authentication now uses the `X-API-TOKEN` header exclusively; the `?token=` query-string fallback has been removed
* Invoice and quote PDFs on the classic, modern, studio, photographer, monochrome and compact templates now print the document status as a labelled line, not only as the watermark
* The editorial and friendly invoice/quote PDF templates no longer print some money values in brown with no status meaning; the invoice balance (both templates) and the quote editorial total now use the same neutral ink as every other template. Labels and headings keep a warmer accent colour instead, now meeting contrast on every background they appear on
* Sending an invoice now requires a `POST` request with a valid CSRF token; the old `GET` link no longer works
* Added a **Refer & earn** page to the profile, a footer link and a small dashboard card for the SolidInvoice Cloud affiliate program, plus a one-off corner prompt on the dashboard a few days after onboarding. Self-hosted installs show it too; set `SOLIDINVOICE_AFFILIATE_PROGRAM_ENABLED=0` to turn it off
* Invoice and quote line quantities are now stored with six decimal places instead of as a floating-point number
* A line description is now optional
* Invoice reminder and overdue scans are now backed by an index on due date and status

### Fixes

* Cancelling an invoice, cancelling a quote and declining a quote now show an info message instead of a success message, since none of them is good news. Archiving is unchanged.
* Cancelling, archiving or activating a recurring invoice now says "Recurring invoice" instead of "Invoice".
* Quote transition messages now use sentence case, matching the invoice side ("Quote has been archived" instead of "Quote has been Archived").
* The PDF status watermark now shows the translated status label instead of the raw status value, so it is no longer always in English.
* Viewing an invoice or quote no longer rewrites every line's row and bumps its last-updated timestamp. Reading the totals used to mark each line as changed even when nothing did.
* Fixed the migration chain so installing or upgrading on MariaDB and PostgreSQL applies the `company_id` backfill and ID-to-ULID conversions correctly; they were silently skipped or failed outright on these platforms before
* Added the `messenger_messages` table to the migration chain. It was previously missing, so an instance upgraded from the tarball or a Helm `pre-upgrade` job would have the failed-message transport create it with a runtime `CREATE TABLE`, which fails on a database user without DDL privileges

### Known issues

* Documents issued **before** upgrading to 3.1.0 are not covered by the currency freeze above. If a client's or your company's default currency changes, pre-upgrade invoices and quotes still restate retroactively. A backfill is planned (tracked as SOL-396/SOL-398) but did not ship in 3.1.0.
* On MySQL, MariaDB and PostgreSQL, upgrading with the in-app upgrade instead of the command line can fail partway through and leave every page showing a server error. See `UPGRADE.md` for the command to run instead, and what to do if you already hit this.
* Overdue status uses the server's UTC clock, not your company's local time zone. If your business is west of UTC (for example, in the Americas), an invoice can show as overdue up to a day before it is actually due. If your business is east of UTC, the overdue reminder can arrive up to a day late. Per-company time zones are planned; this will be fixed then.

3.0.0 / 2026-06-17
-------------------

### Highlights

* Full UI re-design across the entire application: dashboard, client list and view, invoice and quote create/list/view, payments, recurring invoices, tax rates, users, payment configuration, API tokens, integrations, registration, company select, 2FA configuration, email templates and external invoice/quote pages
* Re-designed installation pages, error pages and PDF templates for invoices and quotes
* 8 render-ready invoice and quote PDF templates
* Mobile-friendly responsive layout
* New user onboarding flow with an onboarding checklist
* User email verification
* Google OAuth login
* Two Factor Authentication (2FA)
* First and last name fields added to user accounts
* User-specific settings storage
* Tax system overhaul: multi-tax support, invoice-level tax, and tax rate snapshot freeze
* Add support for the Flat Rate tax type
* Add option to mark invoices as overdue automatically and send notifications
* Add invoice payment reminders
* Add option to create a new client directly from the invoice or quote create page
* Add option to delete a company from settings
* Meilisearch integration for fast cross-company search
* Grid export and full company data export
* Custom fields for Clients and Contacts
* Add Helm charts for Kubernetes deployments
* Multi-platform package distribution support
* Add configurable application URL to installer
* Add option to hide "Powered By" text
* MCP server with OAuth2 authentication
* Cloudflare Turnstile captcha on the registration page
* Opt-in Prometheus metrics for Caddy and FrankenPHP
* Replace Zenstruck Schedule Bundle with Symfony Scheduler
* Add Symfony Messenger integration for asynchronous tasks

### Bug fixes

* Fix company name on external invoice links
* Fix send button on invoice and quote pages
* Handle SMTP transport failures gracefully with proper error messages
* Fix HTML sanitizer encoding special characters in line item descriptions
* Fix enum status handling in PDF templates
* Skip running messenger commands before the application is installed
* Fix editing invoices and quotes
* Fix ORMInvalidArgumentException when marking multiple invoices as overdue
* Fix crash when sending an invoice with no email recipients
* Fix crash when deleting a company
* Fix crash when no payment methods are available
* Fix DataGrid batch action errors
* Fix Settings page crash with invalid section parameter
* Fix TypeError when discount value is null
* Fix TypeError in JSON-LD serializer for Payment resource
* Fix PdfResponse crash caused by slashes in the filename
* Fix NotEnabledTransitionException when publishing a recurring invoice
* Cascade all entities during deletion so nothing is left behind
* Installation command now creates an admin user if one does not exist instead of resetting an existing one

2.3.17
------

* API tokens are now stored as HMAC-SHA256 hashes instead of plaintext. Existing API tokens continue to work. Token values are no longer visible from the management UI after creation; the plaintext is shown exactly once at creation time. Rotating `SOLIDINVOICE_APP_SECRET` now invalidates all API tokens (previously it only invalidated sessions)

2.3.16 / 2026-03-01
-------------------

* Fix HTML sanitizer encoding special characters (such as `&`, `<`, `>`) in line item descriptions
* Handle SMTP transport failures gracefully with proper error messages instead of failing silently

2.3.15 / 2026-02-20
-------------------

* Invoice PDF now shows the invoice date instead of the date the invoice was created
* Display the actual discount amount instead of the raw percentage in invoice and quote grids
* Remove HTML sanitization on contact detail values to allow special characters

2.3.14 / 2026-01-15
-------------------

* Maintenance release with internal cleanup and dependency updates

2.3.13 / 2026-01-12
-------------------

* Fix auto-incrementing of invoice IDs when a prefix is configured
* Fix currency not being set correctly when creating a payment

2.3.12 / 2025-12-27
-------------------

* Fix adding a prefix and suffix to generated invoice and quote IDs

2.3.11 / 2025-12-09
-------------------

* No longer automatically adds default tax rates during installation – tax rates can be configured manually after install
* Fix incorrect currency being displayed in some areas of the application

2.3.10 / 2025-11-03
-------------------

* Update Symfony UX LiveComponent to the latest version for stability and performance fixes

2.3.9 / 2025-10-21
------------------

* Currency codes are now validated to be exactly 3 characters
* Multi-line item descriptions are now displayed correctly on invoices and quotes
* Allow single quotes inside sanitized values (so apostrophes in client/company names are preserved)
* Reduce the size of the company logo on generated PDF files
* Fix calculation of exclusive tax on line items

2.3.8 / 2025-06-20
------------------

* Fix performance issues when loading API tokens

2.3.7 / 2025-06-03
------------------

* Fix the mailer sender address not being read correctly from configuration
* Set a fixed logo size to ensure consistent rendering
* Simplify the registration process
* Compatibility fixes for PHP 8.4

2.3.6 / 2025-05-26
------------------

* Fix the password reset process when no mailer is configured globally
* Remove the requirement for the PHP `gmp` extension
* Allow user invites to be sent when using environment-based mailer configuration

2.3.5 / 2025-05-04
------------------

* Fix database password being incorrectly reset during the installation process
* Migrate all date handling to immutable date objects to prevent accidental mutations
* Simplify the process of adding users to quotes and invoices
* Fix saving line items with decimal quantities
* Fix the billing controller to look up records by UUID
* Improvements to workflow action buttons (send, accept, pay, etc.)

2.3.4 / 2025-05-02
------------------

* Fix issues in the installation process
* Set a default release version when none is provided by the build
* Don't override the config directory if it has already been set (helps with packaged builds)
* Replace Select2 with Tom Select for nicer, faster dropdowns
* Fix email template rendering
* Use the invoice/quote ID when generating PDF filenames

2.3.3 / 2025-04-30
------------------

* Improvements to the static binary build script
* Only fetch recurring invoices that actually have recurring options configured
* Add validation when creating a new company
* Remove unneeded environment variables from the default `docker-compose.yml`
* Fix saving the company address from the settings page

2.3.2 / 2025-04-27
------------------

* Fix the migration that converts UUID values to ULID

2.3.1 / 2025-04-22
------------------

* Only show cron set-up information when not running inside FrankenPHP (where the scheduler is built in)
* Remove empty addresses when saving a new client so blank addresses are not stored

2.3.0 / 2025-04-21
------------------

A large modernisation release with several technical upgrades that may affect users running the application:

### Requirements

* Bump minimum PHP version to **8.1**
* Bump minimum Node version to **18**
* Symfony upgraded from 5.4 to 6.4 and then to **7.x**
* Update API Platform to version **4**

### New features and enhancements

* Add custom invoice and quote number generator with configurable prefix/suffix
* Add explicit invoice date and due date fields to invoices
* Add option to publish (mark as pending) an invoice without sending it
* Add option to add custom (offline) payment gateways
* Add additional fields when capturing payments
* Add support for **SQLite** databases
* Add a static binary distribution built with FrankenPHP
* Skip applicable steps during installation when running under FrankenPHP
* Replace UUID-based primary keys with ULIDs for better ordering and performance
* New DataGrid implementation with cleaner UI and better filtering
* Update settings, contacts, addresses and other forms to use Symfony UX LiveComponent for a smoother experience
* Replace yarn with **bun** as the JavaScript package manager
* Manage configuration through Symfony Secrets
* All environment variables now use the `SOLIDINVOICE_` prefix (existing config can be migrated automatically)
* Convert internal YAML configs to PHP for better performance
* Replace the in-house notification system with **Symfony Notifier**
* Add Codecov bundle analysis to CI

### Bug fixes

* Update the company logo handling and storage
* Fix various ApiPlatform deprecations

2.2.6 / 2024-04-19
------------------

* Fix incorrect option when adding a tax type to a line item
* Fix money data transformer discarding cent values on save
* Fix error when saving contact details with an empty type
* Add a subject line to the password reset request email
* Re-open the entity manager when it has been closed (avoids fatal errors after a failed query)
* Additional Sentry configuration options for self-hosted Sentry instances
* Move environment configuration to a separate, dedicated path

2.2.5 / 2023-09-06
------------------

* Add option to set a global default mailer instead of falling back to a null transport

2.2.4 / 2023-09-06
------------------

* Add Sentry integration for error reporting
* Fix auto-incrementing of invoice and quote IDs
* Fix various currency handling issues
* Fix the actions dropdown on the data grid
* Fix repository methods used when deleting entities
* Filter out PDO drivers that are not supported by Doctrine during installation
* Fix migrations when running on MariaDB 10.5

2.2.3 / 2023-05-15
------------------

* Fix setting tax rates on invoices and quotes
* Fix adding users to quotes and invoices

2.2.2 / 2023-05-01
------------------

* Fix the BackGrid object formatter
* Update Docker images
* Update API docs to expose more information
* Add an event listener that updates a user's last login timestamp
* Fix updating the company name not propagating to all references
* Pre-fill default data when creating a new company

2.2.1 / 2023-04-17
------------------

* Fix creating recurring invoices
* Fix the quote workflow transition when sending a quote
* Fix saving payment IDs
* **[BC break]** Replace numeric entity IDs with UUIDs across the application; legacy numeric `invoiceId` and `quoteId` fields are still exposed for display
* Fix overlap between client name and amount owing on the invoice list screen
* Fix saving contacts on invoices and quotes
* Fix setting the company when using the API
* Remove a global variable update for MySQL from migrations

2.2.0 / 2023-03-23
------------------

* Add support for **multiple companies** per installation
* Add option to disable the "watermark" status overlay on invoices and quotes
* Replace the in-house cron runner with Zenstruck Schedule Bundle
* Redirect to the system setup wizard when the application has no users
* Upgrade Symfony to **5.4**
* Fix various rendering errors
* Fix sending of quotes and invoices
* Fix multiple currency formatting issues

2.1.2 / 2022-11-12
------------------

* Upgrade Payum to fix payment integration errors with several gateways
* Fix the route used when editing a non-recurring invoice
* Fix the page title on installation pages
* Update theme and JavaScript dependencies

2.1.1 / 2022-09-27
------------------

* Fix installation process to correctly save the database password
* Upgrade Guzzle to version 7
* Small API improvements

2.1.0 / 2022-09-01
------------------

A modernisation release that brings the asset pipeline, mailer and authentication stack up to date.

### Requirements

* Bump minimum PHP version to **7.3.5**, with support added up to **PHP 8**
* Upgrade Symfony to version **4** (and split out individual Symfony components)

### Frontend

* Replace RequireJS with **Webpack Encore** for asset bundling
* Update the UI for more consistency across the application
* Update Node version in Docker from 8 to 12

### Backend

* Replace **SwiftMailer** with **Symfony Mailer**
* Replace **FOSUserBundle** with native Symfony security
* Remove **SyliusFlowBundle**
* Split recurring invoices into a separate entity
* Make payment extensions public by default
* Migrate to Symfony Flex
* Migrate from Travis CI to GitHub Actions
* Add Rector for automated refactoring
* Replace Behat with Symfony Panther for functional tests
* Upgrade PHPUnit to version 8
* Add CodeQL analysis

### Bug fixes

* Show that the default Invoice/Quote item quantity will be saved if left empty
* Fix online payments
* Fix PDF CSS includes
* Fix various SASS imports and dependency versions

2.0.4 / 2020-03-29
------------------

* Keep logo when updating setting instead of defaulting it back (#298)
* Remove unused parameter in SettingsFormHandler constructor (#297)
* Removed fixed class from top menu (#280)
* Update dependencies (#275)
* Fix SolidInvoice website link in footer (#267)

2.0.3 / 2019-05-23
------------------

* Fix return type on external quote view action (#264)
* Remove icon from payments list page title (#262)

2.0.2 / 2019-05-15
------------------

* Add code of conduct (#258)
* Copy .env.dist to .env after composer install (#261)
* Return api tokens as array to properly display in list (#259)

2.0.1 / 2019-05-07
------------------

* Fix tax text to indicate when no tax is added (#257)
* Fix quote and invoice not always calculating the total (#256)
* Fix module data overrides arrays to objects in compiled mode (#255)
* Fix translator when using a different locale than the default (#254)
* Fix path to console script for cron (#253)
* Update base html layout (#252)
* Ensure required extensions is loaded when printing PDFs (#251)
* Correctly calculate discount when viewing invoices and quotes (#250)
* Fix incorrect default theme name when running migrations (#248)
* Move read_only option to form attributes (#249)

2.0.0 / 2019-05-05
------------------


2.0.0-RC / 2019-05-05
---------------------

* Use env values for database config if they exist (#246)
* Catch driver and locale exceptions if the proper values don't exist in the environment (#245)
* Add support for Docker Compose (#247)

2.0.0-beta2 / 2019-05-04
------------------------

* Ensure all archived items are deleted when deleting a client (#244)
* Fix modal events and loader. Fix client credit handling (#243)
* Apply select2 to when new items are added in a form collection (#242)

2.0.0-beta1 / 2019-05-02
------------------------

* Simplify Doctrine test entity to make tests run faster (#235)
* Update UI to custom theme (#240)
* Switch Twig classes from underscore to namespaces (#241)
* Store logo in DB instead of filesystem (#239)
* Allow grid rows to be clicked to direct to the relevant record (#238)

2.0.0-alpha3 / 2019-03-30
-------------------------

* Add support for multiple databases (#231)
* Remove unused doctrine extensions and the softdeletable filter (#236)
* Link quotes to invoices when accepting a quote (#228)
* Update email templates to a more modern look and feel (#237)
* Use FQCN for entities instead of namespaces (#233)
* Change all template references to use the Symfony preferred syntax (#232)
* Do not automatically accept and send an invoice when creating from a quote (#230)
* Redirect to created invoice when accepting a quote (#229)
* Add more links for easier navigation (#227)
* Update PHPStan to the latest version
* Remove local copy of backgrid
* Create PDF quotes and invoices (#226)
* Remove conflicting packages from composer.json
* Select default client and contact when creating a new quote or invoice (#224)
* Remove Hipchat from notifications (#221)
* Refactor Email processing (#223)
* Move discount clearing to invoice and quote save listener instead of form listener
* Use full path instead of relative path for modules
* Add line break after action buttons on client create/edit page to remove overlapping with footer
* Clear discount type when no discount is set (#216)
* Don't set quote items by reference (#217)
* Bind modal event to the correct scope (#219)
* Fix totals not updating if no values are set (#218)
* Fix Router script
* Add Single entrypoint (#215)
* Reduce minimum PHP requirements to 7.1
* Dont decode empty settings
* Add server_version to doctrine/dbal configuration

2.0.0 Alpha 2 / 2018-03-29
--------------------------

* Add multi-user support (#195)
* Update Dependencies (#202, #199)
* Add button to print quotes and invoices (#193)
* Display literal country name (#197)
* Fix RequireJS not being included anymore
* Add server_version to doctrine/dbal configuration

2.0.0 Alpha 1 / 2017-08-22
--------------------------

* Rename CSBill to SolidInvoice
* Save users on invoices and quotes in a linking table (#184)
* Use constant when displaying application name (#186)
* Set proper required PHP version in AppRequirements (#185)
* Replace FOSRestBundle with api-platform (#178)
* Add support for monetary discount values (#182)
* Update Vat rates (#181)
* Revamp UI (#179)
* Update JS loading (#177)
* Update mailer to use env values (#174)
* Replace Encryption class with defuse/encryption library (#175)
* Refactor the system settings and config (#173)
* Update Marionette to V3 (#170)
* Update form handlers to use proper options (#172)
* Add invoice and quote cloner (#171)
* Replace finite state machine with Symfony workflow (#166)
* Replace controllers with actions (#165)
* Update all files to PHP 7 strict types (#163)
* Update config to a standardized format (#162)
* Update Twig to 2.0 (#160)
* Move bundles one folder up (#159)
* Update dependencies to the latest versions (#154)
* Update FOSUserBundle to 2 (#153)
* Upgrade PHPUnit to the latest version (#149)

1.1.0 / 2017-02-15
------------------

* Add multi-currency support (#148)
* Fix JS translations not setting global Translator variable anymore (#126)

1.0.3 / 2016-12-12
------------------

* Fix credit modal displaying behind the backdrop overlay (#127)

1.0.2 / 2016-10-15
------------------

* Allow to set custom config path (#125)
* Fix additional details display on contact card (#124)
* Fix address not pulling through to Google maps (#123)
* Disable running of cron if application is not installed (#122)
* Update travis config to not install composer dependencies from source

1.0.1 / 2016-09-21
------------------

* Remove references to primary details on contact (#119)
* Fix incorrect usage of invoice form type (#120)
* Add migrations to set factory for payment methods (#121)

1.0.0 / 2016-09-20
------------------

* Move email from contact_details to contact table (#117)

0.8.1 / 2016-08-10
------------------

* Remove index rename in 0.8 migrations

0.8.0 / 2016-08-08
------------------

* Format payments as money (#116)
* Format discount properly in grid (#115)
* Edit and Delete addresses from client view (#114)
* Fix contacts not deleting when updating a client (#113)
* Update Tests (#111)
* Don't display pay button if invoice is already paid (#112)
* Update payments to use dynamic gateways
* Add Symfony 3 compatibility
* Skip user creation if user already exists during installation
* Fix requirements check exiting when installing through the command line
* Fix invalid locale and currency when passed through command line flags
* Fixed repository fetch
* Update other dependencies
* Upgrade Symfony to V2.8
* Upgrade Doctrine to V2.5

0.7.0 / 2016-06-20
------------------

* Fix datagrid not filtering correctly
* Added related api content for a client
* Added get api method for quotes and invoices
* Allow user to generate a token by sending a username and password to a login url
* Added token manager, to handle token generation in a more generic place
* Fix missing property
* Add api route to get a specific client
* Change token header to X-API-TOKEN
* Added custom error pages for json and jsonp exceptions
* Fix API token create title
* Show quotes grid on client info page
* Remove loader for data grid, since it didn't always hide after loading was done
* Update login page style
* Fix settings variable overwritten on settings page
* Update logo in header when a new logo is uploaded
* Update secret generation to use less bits
* Fix email settings not showing the proper config when switching between smtp and gmail
* Remove assetic
* Added task to watch and compile templates
* Added gulp tasks for assets
* Remove PHP 7 from allowed failures in travis
* Remove salt form user and use auto-generated salt from password_hash
* Update dependencies and update min PHP version to 5.6
* Update doctrine/migrations to allow PHP 5.4
* Fix install command not validating currency and locale correctly
* Refactor menu into separate bundle
* Render recurring invoices grid on client view
* Properly show and hide modals on grid request
* Added recurring invoices grid
* Remove APYDataGrid
* Replaced invoice grid with new grid
* Added multiple grid handling
* Fix installation scripts
* Fix asset url error when no base url is set
* Update email settings scripts
* Update logo upload and cron settings
* Update Symfony to latest 2.7 version
* Handle fields when editing a quote or invoice
* Fix label translations and set tax label on invoice and quote tables
* Calculate totals when saving quotes and invoices
* Cleanup invoice create and split modules
* Disable tax if no tax methods is configured
* Display recurring info
* Added module for invoice create and client select view
* Update API token index page
* Update select2
* Optimize client view
* Update client info view
* Added basic validation using Parsley.js
* Added currency helper to handlebars
* Fix routing module and add path helper for handlebars
* Added handlebars for templates and created generic modal template
* Added basic credit display to client view
* Split some default components into separate modules
* Added lodash library
* Update jQuery to version 2
* Replace underscore with lodash
* Added initial requirejs config
* Fix create button to not show when user is not logged in

0.6.0 / 2015-12-22
------------------

* Improve Navigation
* Added Omnipay payment gateways
* Add extra payment gateway
* Add option to clone invoices and quotes
* Don't allow paid invoices to be edited
* Don't allow the only contact on a client to be deleted
* Add recurring info to invoice view
* Added datepicker to recurring invoices
* [InvoiceBundle] Add support for recurring invoices
* Added material theme
* Added base url to installation and to asset configuration
* Fix logo upoad not displaying
* Don't add payment button to email if no payment methods are configured
* Fix notification templates
* Fix payment methods query when no payment methods is configured
* Fix money formatter
* [InstallBundle] Migrate database if version is outdated
* [InstallBundle] Check if user exists in database when installing application

0.5.0 / 2015-10-14
------------------

* Added REST API
* Fixed modal backdrop overlay
* [ApiBundle] Save history of all API calls
* Add token authentication for API
* Fix discount showing incorrectly
* Remove dependency on the intl extension
* Don't display internal payment methods to client when paying an invoice
* Update email templates for invoices and quotes
* Fix #61: Check invoice total instead of balance if invoice is fully paid
* Added MoneyBundle to implement Fowler's money pattern
* Added current application version as asset version
* [CronBundle] Added CronBuundle
* [InstallBundle] Add cronjob message to cli installer success

0.4.3 / 2015-08-16
------------------

* Added the security:check to travis
* Update dependencies
* [InstallBundle] Change secret to 32 bits instead of 64, to avoid algorithm key size error
* [InstallBundle] Added command line installer
* Fix client grid credit column
* Set invoice balance when converting a quote to an invoice
* Fix error when trying to create a payment

0.4.2 / 2015-07-01
------------------

* [PaymentBundle] Simplify payment method settings to not rely on services to be created
* Add contact types to database migrations
* Remove fixtures from installation process
* Set default email from name and address
* Move migrations to more version specific files
* Fix confirm dialog styles
* Load Router JavaScript earlier to fix Router variable not defined
* Fix InvoiceManager Unit test

0.4.1 / 2015-06-01
------------------

* Fix incorrect invoice status when creating new invoices
* Fix config section names for notifications

0.4.0 / 2015-06-01
------------------

* [CoreBundle] Add setting to BCC copy of quote/invoice to specific address
* [NotificationBundle] Add new Notification bundle
* Update login page style
* Added new logo
* Change client view to use tabs for info
* [TaxBundle] Extract tax functionality to separate bundle
* [ClientBundle] [DataGridBunde] Add support for Grid collections, so that you can display multiple grids on the same page
* [DataGridBundle] Updates to DataGrid
* Added support for credits
* [PaymentBundle] Refactored payment process
* [InstallBundle] Add installation step to create database if it does not yet exist
* Updated settings
* Update installation process
* Updated dashboard
* Added custom error pages
* Added Address support for clients
* Updated minimum required PHP version to 5.4.0

0.3.0 / 2014-09-17
------------------

* Added support for tax rates
* [InstallBundle] Execute database fixtures directly instead of running in a process
* [InstallBundle] Execute database migrations directly instead of running in a process
* [InstallBundle] Fixed installation not working correctly
* [InstallBundle] Simplified the LICENSE file check
* Removed JMSDiExtraBundle
* [CoreBundle] Remove empty test
* [InvoiceBundle] Fix invoice manager bugs
* [InvoiceBundle] [QuoteBundle] Added terms and notes to invoices and quotes
* [CoreBundle] Load app name from settings
* [InstallBundle] Fix install check when database connection can't connect

0.2.0 / 2014-07-29
------------------

* Added button on dashboard to create new client/quote/invoice
* Added Dashboard info
* [PaymentBundle] Added grid to view all payments
* [PaymentBundle] Add icon to pay now button
* [QuoteBundle][InvoiceBundle] Add email icon to send buttons
* [PaymentBundle] Mark offline payments as success by default
* [PaymentBundle] Add support for dropdown settings in payment settings
* [PaymentBundle] Updated PaymentRepository with correct methods
* [ClientBundle] Added list of payments to client view
* [PaymentBundle] Added completed column to payments
* [PaymentBundle] Add error to flash message
* [InvoiceBundle] Add button to pay invoice to email
* [ClientBundle] Fix client edit
* [PaymentBundle] Use original password when saving payment method instead of saving it as NULL
* [PaymentBundle] Set default status to payment
* [CoreBundle] Add checkbox class to checkbox fields
* [PaymentBundle] Use select2 for payment method form
* [PaymentBundle] Only show public payment methods to non-loggedin users
* [PaymentBundle] Change payment method selection to use select2
* [PaymentBundle] When marking invoice as paid, add option to capture a payment
* [QuoteBundle] Fix total column type for Grid
* [QuoteBundle] Fix Select2 for Quotes
* [PaymentBundle] Don't show payment options if no payment methods is configure
* [PaymentBundle] Throw exception when loading invalid invoice
* [CoreBundle] Render external invoices and quotes with normal header
* Fix various page titles and add link to client in invoice and quote view
* [PaymentBundle] Capture payment failure message
* [InvoiceBundle] Fix payment route for invoice list
* [PaymentBundle] Change payment capture page to use invoice UUID instead of id
* [InvoiceBundle] Add payments to invoice view
* [PaymentBundle] Split payment_details table into two tables
* [InvoiceBundle] Don't show payment button if invoice is not pending
* [InvoiceBundle] Add payment icon to invoice index
* [PaymentBundle] Add enabled option to payment methods
* [PaymentBundle] Use custom API to get payment settings
* [PaymentBundle] Added controller for creating payments and dded Paypal Express and offline payment methods
* [PaymentBundle] Added CRUD for payment methods
* [PaymentsBundle] Added payments menu item to system menu
* [CoreBundle] Moved settings to new System menu
* [PaymentBundle] Added payment bundle

0.1.0 / 2014-06-17
------------------

* [QuoteBundle] [InvoiceBundle] Fix column size when creating a quote/invoice
* Fix page overflowing
* [InvoiceBundle] Fix user not added to invoice
* Changed mode on files
* Fixed CS
* Added VersionEye badge to Readme
* Replace chosen with Select2
* Update dependencies
* Clear container file after saving settings to reload new settings
* [CoreBundle] Fixed discound in twig function
* [QuoteBundle] [InvoiceBundle] Fix discount on invoices and quotes
* Added license header to all files. Fixes #5
