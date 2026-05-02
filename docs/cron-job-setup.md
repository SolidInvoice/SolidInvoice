---
description: Setting up a cron job
---

# Cron Job Setup

This documentation outlines how to set up a cron job to automate SolidInvoice's recurring invoices on various platforms. The cron job ensures that the software regularly generates and sends out recurring invoices.

{% hint style="info" %}
Note

When running from the single binary, it's not necessary to set up a cron job. Recurring tasks run automatically.
{% endhint %}

***

### Table of Contents

* [Linux](cron-job-setup.md#linux)
* [Windows](cron-job-setup.md#windows)
* [cPanel](cron-job-setup.md#cpanel)
* [Plesk](cron-job-setup.md#plesk)

***

### Linux

1. Open your terminal.
2. **Edit Crontab**:
   1. Type `crontab -e` and press Enter.
3. **Add Job**:
   1.  Add the following line at the end of the file.

       ```bash
       * * * * * /usr/bin/php /path/to/solidinvoice/bin/console schedule:run
       ```
4. Save the file and exit.

***

### Windows

1. **Open Task Scheduler**: Search for "Task Scheduler" and open it.
2. **Create Task**: Click "Create Basic Task."
3. **Name Task**: Name the task "SolidInvoice."
4. **Trigger**: Set the task to run "Daily" and then "Repeat every 1 minutes."
5. **Action**: Add the action to start the program `php.exe` with arguments

```bash
/path/to/solidinvoice/bin/console schedule:run
```

***

### cPanel

1. **Log In**: Access cPanel and log in.
2. **Navigate**: Go to "Advanced" > "Cron Jobs."
3.  **Add Cron Job**: Add a new cron job with the following settings.

    * Minute: `*`
    * Command:

    ```bash
    /usr/bin/php /home/yourusername/public_html/path/to/solidinvoice/bin/console schedule:run
    ```

***

### Plesk

1. **Log In**: Access Plesk Panel.
2. **Navigate**: Go to "Scheduled Tasks" > "Add Task."
3. **Configure**: Set:
   * Minute: `*`
   * Command:
     * ```bash
       /usr/bin/php /path/to/solidinvoice/bin/console schedule:run
       ```
4. **Save**: Save the task.

***

**Note**: Always replace `/path/to/solidinvoice` with the actual path to your SolidInvoice installation.
