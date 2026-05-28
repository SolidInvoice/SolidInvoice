---
title: Facebook OAuth
description: Let users sign in to SolidInvoice with their Facebook account.
sidebar_position: 4
---

# Facebook OAuth

SolidInvoice can use Facebook as an identity provider, letting users sign in or register with a Facebook account instead of an email and password. Users who already have a SolidInvoice account can also link it to Facebook from their profile page so they can sign in with either method afterwards.

The integration is optional. With no Facebook client configured, the application uses email/password authentication only.

## What this enables

When the integration is configured, three new entry points appear in the UI:

- A `Login with Facebook` button on the login page.
- A `Sign up with Facebook` button on the registration page (only when public registration is enabled — see [Registration through Facebook](#registration-through-facebook)).
- A `Facebook Account` row in `/profile` → `Security`, where a signed-in user can link their existing account to a Facebook identity. Once linked, the row shows a `Linked` badge.

Behind the scenes, when a user completes the Facebook flow, SolidInvoice does the following in order:

1. If a SolidInvoice user already has the returning Facebook ID, they are signed in as that user.
2. Otherwise, if a SolidInvoice user has the same email address as the Facebook account, the Facebook ID is attached to that user and they are signed in.
3. Otherwise, if a user is already signed in (the profile-page link flow), the Facebook ID is attached to the current user.
4. Otherwise, if public registration is enabled, a new user is created using the email returned by Facebook (Facebook only releases verified email addresses, so the user is treated as already email-verified).
5. Otherwise, authentication is rejected with an error message on the login page.

When the user signs in or registers via Facebook, SolidInvoice also fills in the first name and last name from Facebook — but only if those fields are currently empty on the user. Any name a user has already saved is never overwritten.

## Create a Facebook OAuth client

Before SolidInvoice can talk to Facebook, you need a Meta for Developers application.

1. Open the [Meta for Developers](https://developers.facebook.com/) site and create a new app. Choose the `Consumer` use case (`Other` works as well).
2. In the app dashboard, open `App Settings` → `Basic` and copy the `App ID` and `App Secret`.
3. Add the `Facebook Login` product to the app.
4. Under `Facebook Login` → `Settings`, add the SolidInvoice OAuth check URL as a `Valid OAuth Redirect URI`:

   ```text
   https://your-solidinvoice-domain.example/oauth/check/facebook
   ```

   The path is always `/oauth/check/facebook`. Add one entry per environment (production, staging, local development).
5. Switch the app to `Live` mode once you have completed Meta's app review for the `email` permission. While in development mode, only test users and app admins can sign in.

:::warning
The redirect URI must match exactly — including the scheme (`http`/`https`), host, and trailing path. Mismatches show up as a `URL Blocked` error from Facebook after the user clicks the sign-in button.
:::

## Configure SolidInvoice

Set two environment variables on the SolidInvoice instance, then restart the application:

| Variable | Description |
| --- | --- |
| `SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_ID` | The `App ID` from Meta for Developers. |
| `SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_SECRET` | The `App Secret` from Meta for Developers. |

Both variables must be set for the integration to activate. Leaving either empty disables the Facebook buttons everywhere in the UI.

For Docker:

```bash
docker run \
  -e SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_ID=... \
  -e SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_SECRET=... \
  solidinvoice/solidinvoice
```

For the distribution package and source installs, add the values to `.env` at the root of the application:

```ini title=".env"
SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_ID=1234567890123456
SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_SECRET=your-facebook-app-secret
```

:::tip
If the Facebook buttons don't appear after setting the variables, clear the application cache: `bin/console cache:clear`.
:::

## Signing in with Facebook

On the login page, click `Login with Facebook`. The browser is redirected to Facebook, the user authorizes the SolidInvoice application, and Facebook redirects back to `/oauth/check/facebook`. SolidInvoice signs the user in (matching by Facebook ID first, then by email) and redirects to the company selector.

Already-existing accounts are matched on email automatically — a user who originally signed up with an email/password and later clicks `Login with Facebook` will end up signed into their existing account, with the Facebook ID stored for future logins.

## Registration through Facebook

When public registration is enabled on your instance, the registration page shows a `Sign up with Facebook` button alongside the email/password form. Clicking it follows the same Facebook flow; if no SolidInvoice user matches the returning email, a new user is created with:

- The email address returned by Facebook.
- A pre-verified status — Facebook only releases email addresses that have been verified on the Facebook side, so the SolidInvoice email verification step is skipped.
- The first and last name reported by Facebook.
- A randomly generated password that is never shown — the user can sign in only via Facebook until they set a password through the password reset flow.

If public registration is disabled, the `Sign up with Facebook` button is hidden, and Facebook sign-in is rejected for any email that doesn't already have a SolidInvoice account.

## Linking an existing account

Users who already signed up with email/password can link their account to Facebook from the profile page. While signed in, navigate to `/profile`, scroll to the `Security` section, and click `Sign in with Facebook` on the `Facebook Account` row. After completing the Facebook flow, the row updates to show a `Linked` badge, and the user can sign in with either Facebook or their original password from then on.

:::info
A SolidInvoice account can be linked to one Facebook account at a time. If a user wants to switch the linked Facebook identity, they need to unlink the current one through database access — there is currently no in-app unlink button.
:::

## Troubleshooting

### `URL Blocked` from Facebook

The redirect URI configured under `Facebook Login` → `Settings` does not exactly match the URL SolidInvoice is calling back on. Compare scheme, host, and path — the path must be `/oauth/check/facebook`, and the scheme and host must match the public URL of your installation.

### Facebook buttons don't appear on the login or registration page

Both `SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_ID` and `SOLIDINVOICE_OAUTH_CLIENT_FACEBOOK_CLIENT_SECRET` must be set and non-empty. After changing either variable, clear the application cache with `bin/console cache:clear` and reload the page.

### Authentication is rejected after the Facebook flow

Public registration is disabled and the Facebook account's email is not associated with an existing SolidInvoice user. Either enable registration so the user can be created automatically, or have an admin create the user first with the matching email address.

### Only test users can sign in

The Meta for Developers app is still in `Development` mode. Until the app has been switched to `Live`, only users added under `App Roles` → `Roles` and `Testers` can complete the Facebook flow. Submit the `email` permission for review and flip the app to `Live` to allow public sign-in.
