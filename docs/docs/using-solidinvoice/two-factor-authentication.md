---
title: Two-factor authentication
description: Protect your SolidInvoice account with a second verification step at login.
sidebar_position: 2
---

# Two-factor authentication

Two-factor authentication (2FA) adds a second verification step after your password. Even if someone learns your password, they still need your phone or email to sign in.

## Open 2FA settings

In the left sidebar, click your name or avatar to open the profile menu, then choose `Two-Factor Authentication`. You can also navigate directly to `/profile/2fa`.

## Choose an authentication method

SolidInvoice supports two independent 2FA methods. You can enable one or both.

### Email authentication

When enabled, SolidInvoice emails you a 6-digit code each time you log in. Codes are sent to your account's email address.

Click `Enable` under **Email Authentication** to turn it on. The status badge next to the method changes to `Enabled`.

### Authenticator app (TOTP)

When enabled, you generate 6-digit codes in an app like Google Authenticator, Authy, or any TOTP-compatible app — no internet needed once set up.

1. Click `Enable` under **Authenticator App**. A **Set Up Authenticator App** dialog opens.
2. Open your authenticator app and scan the QR code shown. If you can't scan it, click `Can't scan? Enter manually` to reveal the secret key — type it into your app instead.
3. Your app generates a 6-digit code. Enter it in the `Enter the 6-digit code from your app` field.
4. Click `Verify & Enable`.

## Backup codes

When you enable any 2FA method, SolidInvoice generates a set of single-use backup codes. Use one if you lose access to your phone or email.

The **Backup Codes** section shows how many codes remain. From there you can:

- **View Codes** — display all remaining codes.
- **Download** — save them as a text file (`solidinvoice-backup-codes-YYYY-MM-DD.txt`). Store this somewhere safe.
- **Regenerate Codes** — invalidates all existing codes and creates a fresh set.

:::warning
Each backup code can only be used once. Regenerating codes permanently invalidates any you haven't used yet.
:::

## Signing in with 2FA

After entering your password on the login page, you are redirected to a verification screen. Enter the 6-digit code from your email or authenticator app.

Check `Trust this device for 30 days` if you're on a personal computer you use regularly — you won't be asked for a code on that device for 30 days.

### Using a backup code

On the verification screen, choose the alternative method and enter one of your backup codes instead of a 6-digit code.

## Trusted devices

If you ticked `Trust this device` during login, the **Trusted Device** section appears in your 2FA settings. Click `Revoke Trust` to require 2FA on this device again immediately.

## Disabling 2FA

Click `Disable` next to the method you want to turn off. If you disable all methods, your backup codes are also cleared.

## Related

- [Updating your profile](./user-profile.md)
- [Google OAuth login](../integrations/google-oauth.md)
