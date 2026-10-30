---
title: User profile
description: Update your personal details, change your password, and manage account settings.
sidebar_position: 4
---

# User profile

Your profile page brings together your personal details and account settings in one place.

## Open your profile

Click your name or avatar in the left sidebar. The profile page shows your current information under two sections: **Personal Information** and **Security**.

## Update personal details

Click `Update Profile` to open the edit form.

| Field | Notes |
| --- | --- |
| `First Name` | Appears in the UI and on outgoing emails. |
| `Last Name` | Appears in the UI and on outgoing emails. |
| `Email` | Your login email. Changing it triggers a new [verification email](./email-verification.md). |
| `Mobile` | Optional contact number. |
| `Current Password` | Required to confirm any change to the form. |

Click `Save` when done. You are returned to the profile page with a confirmation message.

## Change your password

From the **Security** section, click `Change Password`. This opens a dedicated form where you enter your current password and choose a new one.

## Two-factor authentication

The **Security** section shows whether 2FA is active on your account. Click `Enable` or `Manage` to open the [Two-Factor Authentication](./two-factor-authentication.md) settings.

## Notification preferences

From the sidebar, choose `Notifications` under your profile. You can choose which events trigger notifications and which channels receive them (email is always available; other channels depend on your configured integrations).

Every notification is sent to you by email until you turn it off. This covers new accounts, companies you join or create, and any notification added in a future update. Clear the `Email` box next to an event and save to stop those emails.

Each notification email names the company it was sent for and links back to this page.

Notifications only go to active accounts. If your account is disabled, or you have not yet verified your email address, you get no notifications on any channel until that is resolved.

Notifications you can turn on or off include:

- **Recurring Invoice Created**: sent when SolidInvoice creates an invoice from one of your recurring invoices. The email lists the invoice number, client, dates, line items and totals, and links to the invoice.

## API tokens

From the sidebar, choose `API Keys` to create, view, and revoke personal API tokens used to authenticate requests to the [REST API](../api/rest-api.md).

## Connected apps

From the sidebar, choose `Connected Apps` to see and revoke OAuth applications that have been granted access to your account.

## Data export

From the sidebar, choose `Data Export` to request a full export of your company data. See [Exporting data](./exporting-data.md) for details.

## Related

- [Two-factor authentication](./two-factor-authentication.md)
- [Email verification](./email-verification.md)
- [Exporting data](./exporting-data.md)
