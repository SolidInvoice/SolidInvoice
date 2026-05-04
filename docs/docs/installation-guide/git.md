---
title: Git (advanced)
description: Clone the SolidInvoice source for contributing or hacking on the code.
sidebar_position: 6
---

# Git (advanced)

:::warning
Installing from Git is intended for contributors and developers who want to hack on the SolidInvoice source. **It is not recommended for production use** — for that, use the [quick install](./quick-install.mdx), [Homebrew](./homebrew.md), or [Docker](./docker.md).
:::

## System requirements

- PHP 8.4 or higher with the `curl`, `gd`, `intl`, `json`, `openssl`, `pdo`, `soap`, and `xsl` extensions.
- [Composer](https://getcomposer.org/).
- [Bun](https://bun.sh/).
- [Symfony CLI](https://symfony.com/download) — recommended for the local web server (see below). Optional if you bring your own webserver.
- A supported database (MySQL, MariaDB, PostgreSQL, or SQLite).

## Clone and install

```bash
git clone https://github.com/SolidInvoice/SolidInvoice.git
cd SolidInvoice
composer install
bun install
bun run build
```

## Run the local web server

The recommended way to run SolidInvoice for development is the [Symfony CLI](https://symfony.com/doc/current/setup/symfony_cli.html#running-the-local-web-server) — it ships a local web server with HTTPS, Docker integration, and a workers manager.

```bash
symfony serve
```

This reads the project's `.symfony.local.yaml` and:

- Starts an HTTPS web server on port `7005` (configurable in `.symfony.local.yaml`).
- Starts the async messenger consumer as a managed worker, so scheduled tasks and async messages (emails) are processed without you having to run `messenger:consume` separately.

The committed `.symfony.local.yaml` already contains both the HTTP and worker configuration:

```yaml title=".symfony.local.yaml"
http:
    document_root: public/
    passthru: index.php
    port: 7005
    preferred_port: 7005
    allow_http: true
    daemon: true

workers:
    messenger_consume:
        cmd: ['symfony', 'console', 'messenger:consume', 'async', '--time-limit=3600', '--memory-limit=128M']
        watch: ['config', 'src']
```

The `watch` paths restart the worker whenever you change application code, so it picks up your edits automatically.

To follow the worker logs alongside the web server logs:

```bash
symfony server:log
```

## Bring-your-own webserver

If you'd rather use Nginx or Apache directly, point the document root at `public/` (see the [distribution package guide](./distribution-package/index.mdx#2-configure-the-webserver) for example configs) and set up the [background worker](./distribution-package/cron-job-setup.md) the same way.

## Finish setup

Open the URL Symfony CLI prints (typically `https://127.0.0.1:7005`) and finish setup with the [first-run wizard](./system-installation.md).

For development workflow, code conventions, and how to run the test suite, read [`CONTRIBUTING.md`](https://github.com/SolidInvoice/SolidInvoice/blob/3.0.x/CONTRIBUTING.md) in the repository.

If you encounter issues, please [open a bug report](https://github.com/SolidInvoice/SolidInvoice/issues).
