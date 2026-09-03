---
title: Proxmox VE
description: Install SolidInvoice in a Proxmox VE LXC container using the Community Scripts helper script.
sidebar_position: 5
---

# Proxmox VE

The [Community Scripts](https://community-scripts.org/scripts/solidinvoice) helper script creates a Debian LXC container on your Proxmox VE host, installs SolidInvoice into it, and registers it as a systemd service — one command, no manual container setup.

## System requirements

- A Proxmox VE host.
- Run the command from the **host** shell — the node's `Shell` in the Proxmox web UI, or over SSH. Not from inside an existing container.

## Install

```bash
bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/solidinvoice.sh)"
```

The script asks whether to use default or advanced settings. The defaults are:

| Setting | Default |
| --- | --- |
| Operating system | Debian 13 |
| CPU | 2 cores |
| RAM | 2 GB |
| Disk | 4 GB |
| Container type | Unprivileged |

Choose `Advanced` at the prompt to change the container ID, hostname, resources, or network settings.

When the script finishes it prints the container's URL. SolidInvoice listens on `http://<container-ip>:8765` — open that in your browser and finish setup with the [first-run wizard](./system-installation.md).

:::info
The container serves plain HTTP. For production, place SolidInvoice behind a reverse proxy (Nginx, Caddy, Traefik) that handles TLS termination.
:::

## Configuration

Inside the container, the service reads its configuration from `/etc/solidinvoice/solidinvoice.env`. The file ships fully commented — edit it to change the port, point at an external database, or set a mail transport:

```ini title="/etc/solidinvoice/solidinvoice.env"
# Port to listen on (default: 8765)
#SOLIDINVOICE_PORT=8765

# Database connection string — defaults to SQLite when unset
#SOLIDINVOICE_DATABASE_URL=mysql://user:password@127.0.0.1:3306/solidinvoice
```

Restart the service to apply changes:

```bash
systemctl restart solidinvoice
```

## Manage the service

Run these inside the container — open its console from the Proxmox web UI, run `pct enter <ctid>` on the host, or connect over SSH:

```bash
systemctl start solidinvoice     # start
systemctl stop solidinvoice      # stop
systemctl restart solidinvoice   # restart
systemctl status solidinvoice    # check status
journalctl -u solidinvoice -f    # follow logs
```

## File locations

| Path | Contents |
| --- | --- |
| `/usr/bin/solidinvoice` | Binary |
| `/etc/solidinvoice/solidinvoice.env` | Configuration |
| `/etc/solidinvoice/` | Generated secrets and the SQLite database |
| `/var/lib/solidinvoice/` | Application data |
| `/etc/systemd/system/solidinvoice.service` | systemd unit |

## Update

Run the built-in update command from inside the container:

```bash
update
```

This downloads the latest SolidInvoice release, replaces the binary, and restarts the service. Your configuration and data are left untouched.

:::note
The helper script is maintained by the [Community Scripts](https://community-scripts.org/scripts/solidinvoice) project. Report problems with the script itself on their [GitHub repository](https://github.com/community-scripts/ProxmoxVE); report problems with SolidInvoice on the [SolidInvoice issue tracker](https://github.com/SolidInvoice/SolidInvoice/issues).
:::
