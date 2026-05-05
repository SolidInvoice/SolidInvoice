---
title: Cron job setup
description: Schedule the SolidInvoice background worker on your platform — systemd, cron, Plesk, cPanel, or Windows.
sidebar_position: 2
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Cron job setup

SolidInvoice uses a single background worker for both async messages (email, webhooks) and scheduled tasks (recurring invoices, reminders, overdue checks). The command is the same in every case:

```bash
bin/console messenger:consume --all --time-limit=3600 --memory-limit=128M
```

The platform-specific configurations below are alternative ways to keep that command running. Pick whichever fits your environment.

:::warning
Without a worker running, async features and scheduled tasks won't fire — emails won't send and recurring invoices won't generate.
:::

:::info
You only need to set up a worker when running from the [distribution package](./index.mdx) or from [Git](../git.md). The [quick install](../quick-install.mdx), [Homebrew](../homebrew.md), and [Docker](../docker.md) installs run the worker automatically.
:::

## Pick a platform

<Tabs groupId="cron-platform">
  <TabItem value="systemd" label="systemd" default>

  A long-running systemd service is the most reliable option on a Linux server.

  Create a unit file at `/etc/systemd/system/solidinvoice-worker.service`:

  ```ini title="/etc/systemd/system/solidinvoice-worker.service"
  [Unit]
  Description=SolidInvoice worker
  After=network.target

  [Service]
  Type=simple
  User=www-data
  WorkingDirectory=/opt/solidinvoice
  ExecStart=/usr/bin/php bin/console messenger:consume --all --time-limit=3600 --memory-limit=128M
  Restart=on-failure
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```

  Reload, enable, and start it:

  ```bash
  sudo systemctl daemon-reload
  sudo systemctl enable --now solidinvoice-worker.service
  ```

  For higher throughput, run multiple copies via a systemd template (`solidinvoice-worker@.service`) and start `solidinvoice-worker@1`, `solidinvoice-worker@2`, etc.

  </TabItem>

  <TabItem value="supervisord" label="Supervisord">

  Use [Supervisord](http://supervisord.org/) on systems without systemd (or when your stack already manages other services with it).

  Create a program file at `/etc/supervisor/conf.d/solidinvoice-worker.conf`:

  ```ini title="/etc/supervisor/conf.d/solidinvoice-worker.conf"
  [program:solidinvoice-worker]
  command=/usr/bin/php /opt/solidinvoice/bin/console messenger:consume --all --time-limit=3600 --memory-limit=128M
  user=www-data
  numprocs=1
  process_name=%(program_name)s_%(process_num)02d
  autostart=true
  autorestart=true
  startsecs=5
  startretries=10
  stopasgroup=true
  killasgroup=true
  stopwaitsecs=30
  stdout_logfile=/var/log/supervisor/solidinvoice-worker.log
  stderr_logfile=/var/log/supervisor/solidinvoice-worker.err.log
  ```

  Reload Supervisord and start the worker:

  ```bash
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start solidinvoice-worker:*
  ```

  Increase `numprocs` to run multiple workers in parallel — Supervisord will append the process number to `process_name` automatically.

  :::tip
  `stopwaitsecs=30` gives the worker time to finish the message it's currently handling before being killed. Keep it higher than the slowest message you expect to process.
  :::

  </TabItem>

  <TabItem value="cron" label="Linux cron">

  Use cron when you can't run a long-running service (for example on shared hosting that blocks daemons). The `--time-limit=55` flag makes the worker self-terminate before the next cron tick:

  ```bash title="crontab -e"
  * * * * * /usr/bin/php /opt/solidinvoice/bin/console messenger:consume --all --limit=10 --time-limit=55 --memory-limit=128M
  ```

  :::note
  This approach introduces up to a 60-second delay before async messages and scheduled tasks start processing. For most self-hosted setups this is fine — but use the **systemd** option if you have it.
  :::

  Replace `/opt/solidinvoice` with the actual path to your installation.

  </TabItem>

  <TabItem value="cpanel" label="cPanel">

  1. Log in to cPanel.
  2. Go to **Advanced → Cron Jobs**.
  3. Add a new cron job:
     - **Common Settings:** `Once per minute (* * * * *)`
     - **Command:**

       ```bash
       /usr/bin/php /home/yourusername/path/to/solidinvoice/bin/console messenger:consume --all --limit=10 --time-limit=55 --memory-limit=128M
       ```

  4. Save.

  Replace `/home/yourusername/path/to/solidinvoice` with the actual path to your installation.

  </TabItem>

  <TabItem value="plesk" label="Plesk">

  1. Log in to the Plesk panel.
  2. Go to **Tools & Settings → Scheduled Tasks** (or **Scheduled Tasks** under your domain).
  3. Click **Add Task** and configure:
     - **Task type:** Run a command
     - **Run:** `Cron style — * * * * *`
     - **Command:**

       ```bash
       /usr/bin/php /path/to/solidinvoice/bin/console messenger:consume --all --limit=10 --time-limit=55 --memory-limit=128M
       ```

  4. Save the task.

  </TabItem>

  <TabItem value="windows" label="Windows">

  Use Task Scheduler to run the worker every minute.

  1. Open **Task Scheduler** and select **Create Task**.
  2. **General** — name the task `SolidInvoice worker`.
  3. **Triggers** — add a new trigger:
     - Begin the task: **On a schedule**
     - **Daily**, recur every `1` day
     - **Repeat task every:** `1 minute` for a duration of `Indefinitely`
  4. **Actions** — add a new action:
     - **Action:** Start a program
     - **Program/script:** `php.exe`
     - **Add arguments:**

       ```text
       C:\path\to\solidinvoice\bin\console messenger:consume --all --limit=10 --time-limit=55 --memory-limit=128M
       ```

  5. Save the task.

  Replace `C:\path\to\solidinvoice` with the actual path to your installation.

  </TabItem>
</Tabs>

## Verifying the worker is running

Tail the application log or check the messenger queue:

```bash
bin/console messenger:stats
```

A healthy setup keeps the queue counts low — messages are processed within seconds (systemd) or up to a minute (cron).
