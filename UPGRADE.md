Unreleased
==========

3.1.1 / 2026-10-15
==================

* `SOLIDINVOICE_APP_SECRET` is read correctly again. 3.1.0 ignored it, so the app
  secret changed every time the cache was rebuilt; API tokens, "remember me" logins
  and signed links created while running 3.1.0 stopped working. API tokens created
  under 3.1.0 need to be regenerated after upgrading to 3.1.1.

3.1.0 / 2026-10-15
==================

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
* **Known issue — PostgreSQL only:** upgrading an existing installation (one that
  already has at least one company) to 3.1.0 or 3.1.1 can fail during the
  `Version30100_4` migration. The migration writes an identifier in a format that
  PostgreSQL's `uuid` column rejects, so the upgrade stops partway through.
  MySQL and MariaDB installations are not affected, and neither are fresh installs
  on any database. Earlier migrations in the same upgrade already committed, so no
  data is lost, but the installation is stuck on the old schema until the fix
  lands. Do not upgrade a PostgreSQL installation that already has data to 3.1.0
  or 3.1.1 until 3.1.2 ships with the fix (tracked in SOL-521 and SOL-523; fix:
  https://github.com/SolidInvoice/SolidInvoice/pull/3048).

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
