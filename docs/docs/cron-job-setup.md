---
title: Cron Job Setup
description: Setting up a cron job to automate recurring invoices.
sidebar_position: 3
---

# Cron Job Setup

This guide outlines how to set up a cron job to automate SolidInvoice's recurring invoices on various platforms. The cron job ensures that the application regularly generates and sends out recurring invoices.

:::info
When running from the single binary, you don't need to set up a cron job — recurring tasks run automatically.
:::

## Linux

1. Open your terminal.
2. Edit the crontab by running `crontab -e`.
3. Add the following line at the end of the file:

   ```bash
   * * * * * /usr/bin/php /path/to/solidinvoice/bin/console schedule:run
   ```

4. Save the file and exit.

## Windows

1. **Open Task Scheduler** — search for "Task Scheduler" and open it.
2. **Create Task** — click _Create Basic Task_.
3. **Name Task** — name the task `SolidInvoice`.
4. **Trigger** — set the task to run _Daily_ and then _Repeat every 1 minute_.
5. **Action** — add the action to start the program `php.exe` with the following arguments:

   ```bash
   /path/to/solidinvoice/bin/console schedule:run
   ```

## cPanel

1. **Log In** — access cPanel and log in.
2. **Navigate** — go to _Advanced_ → _Cron Jobs_.
3. **Add Cron Job** — add a new cron job with the following settings:

   - Minute: `*`
   - Command:

     ```bash
     /usr/bin/php /home/yourusername/public_html/path/to/solidinvoice/bin/console schedule:run
     ```

## Plesk

1. **Log In** — access the Plesk Panel.
2. **Navigate** — go to _Scheduled Tasks_ → _Add Task_.
3. **Configure** — set:
   - Minute: `*`
   - Command:

     ```bash
     /usr/bin/php /path/to/solidinvoice/bin/console schedule:run
     ```

4. **Save** — save the task.

:::note
Always replace `/path/to/solidinvoice` with the actual path to your SolidInvoice installation.
:::
