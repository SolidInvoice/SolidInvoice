---
title: System Installation
description: Run the installation wizard.
sidebar_position: 4
---

# System Installation

When navigating to the application for the first time, you'll automatically land on the installation page.

This page shows whether your system meets all the requirements to run SolidInvoice. If your system does not meet the requirements, an error message will explain what you need to change. After fixing any issues, refresh the page (`F5` or the _Refresh_ button).

If there are no errors, proceed by pressing the _Next_ button.

![System requirements check](/img/docs/system-installation-requirements.png)

## Configuration

This step lets you set up the database where all information will be stored.

### Database Configuration

Add your database information. If the database doesn't exist, SolidInvoice will attempt to create it.

![Database configuration](/img/docs/system-installation-database.png)

## Installation Process

At this point, the database is installed and your tables are created.

When the installation is complete and there are no errors, continue with the setup process by pressing _Next_.

![Installation in progress](/img/docs/system-installation-installing.png)

## System Information

The final step is to configure your application and create your first admin user.

![System information](/img/docs/system-installation-config.png)

The following values need to be configured:

| Setting   | Description                                                                                                                                                                                                                              |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Locale    | Determines the language to use, and is also used for currency and number formatting. Choose the correct locale for your country. Only English is supported at the moment, but support for other languages may be added in a future release. |
| Currency  | The default currency for the application.                                                                                                                                                                                                |

When you're done, continue by pressing _Next_.

### Admin User

You need to create an admin user. The provided details will be the credentials you use to log into the system.

## Final Steps

After the setup process is complete, the last step is to set up the cron job.

The cron job is used to run scheduled tasks like recurring invoices. Setting up the cron job will be different based on your hosting provider — see the [Cron Job Setup](../cron-job-setup.md) guide for instructions.

:::warning
If you do not set up the cron job, functionality will be limited and scheduled tasks won't be able to run. It is **highly recommended** to set up the cron job.
:::

When you're ready to use the application, press the _Log in now_ button to log into SolidInvoice.

![Installation complete](/img/docs/system-installation-complete.png)
