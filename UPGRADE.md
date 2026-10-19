Unreleased
==========

3.1.0 / 2026-10-15
===================

* SolidInvoice was upgraded to **Symfony 8.1** and now requires **PHP 8.4.1 or
  higher**.
* The **Sms77** and **Gitter** notification transports were removed, as the
  underlying Symfony bridges are discontinued. If you had a notification
  transport configured for one of these services, configure a different
  transport after upgrading.
* All existing remember-me cookies are invalidated by the upgrade; users simply
  need to log in again.
* Client website URLs are now validated to require a proper domain (URLs such as
  `http://localhost` are rejected).
* Invoice, recurring invoice and quote lines now have a **name** as well as a
  description. Existing lines are named automatically during the upgrade, from
  the first line of their description that has text on it. Where the whole
  description became the name, the description is cleared. A line created or
  edited over the API without a `name` still works: the name is derived from
  the description automatically, whichever order the fields arrive in.
* A line **description is now optional**. It was previously required.
* A line name is limited to **255 characters**. Where the first line of a
  description was longer than that, the name is cut at 255 characters and ends
  with an ellipsis; the description keeps the full text in that case.
* Lines now keep an explicit **order**. Existing lines are numbered in the
  order they were created, so an existing invoice or quote renders exactly as
  it did before.
* The upgrade **rewrites every row** in the invoice-line and quote-line
  tables more than once. Take a database backup before you upgrade, and
  expect the migration to take longer than usual on a large installation.
* Invoices, recurring invoices and clients gain a **payment terms** field. A
  new company setting, "Default payment terms for new invoices", is added
  with a default of **Net 30** — change it under company settings if that
  default is not wanted. Existing invoices and recurring invoices are set to
  **Custom**, which keeps the due date they already had; no existing due date
  changes.
* Issued invoices and quotes now **freeze their currency** the moment they
  leave Draft/New, for documents issued after this upgrade. Previously,
  changing a client's or your company's default currency retroactively
  changed the currency shown on every historical document issued to that
  client, while the stored amounts stayed the same. **Documents already
  issued before you upgrade are not backfilled and remain exposed to this
  behaviour** until a planned follow-up migration ships; after upgrading, a
  newly issued document's currency is locked once issued, but older
  documents keep restating until the backfill lands.
* **If you run MySQL, MariaDB or PostgreSQL: upgrade from the command line,
  not the in-app upgrade.** Run `bin/console doctrine:migrations:migrate`
  right after you deploy the new code or image, before the application
  serves any request. A known defect in the in-app upgrade (tracked as
  SOL-172) makes it fail partway through on these three database engines —
  every page then shows a server error (HTTP 500) instead of finishing the
  upgrade. Running the command above first avoids the defect, because it
  runs the full migration instead of the broken in-app path.
* **If you already let the in-app upgrade run, and every page now shows a
  server error:** stop. Do not re-run the in-app upgrade or the command-line
  migration yourself. Your database schema may now be partly changed with
  nothing recorded as applied, and a safe way to finish from that state has
  not been verified yet. A checked recovery procedure is being written
  (tracked as SOL-173); this note will be replaced with exact steps once it
  exists. Until then, make no further changes to the database and contact
  SolidWorx support or open an issue on GitHub.

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
