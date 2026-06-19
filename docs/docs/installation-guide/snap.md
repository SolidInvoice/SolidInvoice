---
title: Snap
description: Install SolidInvoice from the Snap Store on Ubuntu or any Linux distribution with snapd.
sidebar_position: 4
---

# Snap

SolidInvoice is available on the [Snap Store](https://snapcraft.io/solidinvoice). The snap bundles the self-contained binary and registers it as a background service — no PHP, webserver, or cron job required.

## System requirements

- Linux with [snapd](https://snapcraft.io/docs/installing-snapd) installed. Ubuntu 16.04 and later include snapd out of the box.

## Install

```bash
sudo snap install solidinvoice
```

The service starts automatically after installation and listens on `http://localhost:8765`. Open that URL in your browser and finish setup with the [first-run wizard](./system-installation.md).

:::info
The snap runs over plain HTTP. For production, place SolidInvoice behind a reverse proxy (Nginx, Caddy, Traefik) that terminates TLS.
:::

## Manage the service

```bash
sudo snap start solidinvoice    # start
sudo snap stop solidinvoice     # stop
sudo snap restart solidinvoice  # restart
snap logs solidinvoice          # view logs
snap logs -n 100 solidinvoice   # view last 100 lines
```

## CLI

The snap exposes a `cli` app for running console commands:

```bash
solidinvoice.cli console cache:clear
solidinvoice.cli version
```

## Data

Application data is stored in `/var/snap/solidinvoice/common/`.

## Update

```bash
sudo snap refresh solidinvoice
```

Snaps update automatically in the background by default. Run the above to force an immediate refresh.
