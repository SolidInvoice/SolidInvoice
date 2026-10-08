Unreleased
==========

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
* A percentage discount is now stored as the plain percentage a user typed (`15` for
  15%), not as hundredths. The `Version30100_13` migration updates existing rows so
  every issued document's total stays the same as before the upgrade. A row that
  already held a plain percentage from 1 to 100 cannot be told apart from an old
  hundredths value in that same range, so the migration leaves it alone and records
  it in a new `ambiguous_discount_units` table for manual review.

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
