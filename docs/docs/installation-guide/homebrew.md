---
title: Homebrew
description: Install SolidInvoice from the SolidWorx Homebrew tap on macOS or Linux.
sidebar_position: 3
---

# Homebrew

The Homebrew tap installs the same self-contained build used in the [quick install](./quick-install.mdx) under a managed path and keeps it up to date with `brew upgrade`.

## System requirements

- macOS or Linux with [Homebrew](https://brew.sh/) installed.
- A database — SQLite works out of the box; MySQL, MariaDB, or PostgreSQL are also supported.

No PHP, webserver, or cron job is required.

## Install

```bash
brew install solidworx/tap/solidinvoice
```

## Run

```bash
solidinvoice run
```

The application starts on `https://localhost:8765` with a self-signed certificate. Open the URL in your browser and finish setup with the [first-run wizard](./system-installation.md).

For SSL, custom domains, worker mode, and the full list of `run` flags, see the [quick install guide](./quick-install.mdx#ssl).

:::info
Recurring tasks and async work (email sending) run automatically — there is no separate cron job or messenger consumer to set up.
:::

## Update

```bash
brew upgrade solidworx/tap/solidinvoice
```
