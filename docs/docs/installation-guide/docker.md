---
title: Docker
description: Run SolidInvoice as a Docker container, optionally alongside a database via Docker Compose.
sidebar_position: 4
---

# Docker

The official Docker image runs the same self-contained build used in the [quick install](./quick-install.mdx). Multi-architecture images are published — Docker pulls the right one for your host automatically.

## System requirements

- [Docker](https://www.docker.com/get-started/) installed on the host.
- [Docker Compose](https://docs.docker.com/compose/) if you want to use the bundled compose example.

## Quick start

```bash
docker run -d -p 8765:8765 -v solidinvoice_data:/etc/solidinvoice solidinvoice/solidinvoice
```

The application starts on [http://127.0.0.1:8765](http://127.0.0.1:8765). Continue with the [first-run wizard](./system-installation.md).

:::tip
Change `8765` on the left side of the `-p` flag to expose SolidInvoice on a different host port (e.g. `-p 80:8765`).
:::

## Docker Compose

For a complete stack (app + database), use a `docker-compose.yml` like the one shipped with the repository:

```yaml title="docker-compose.yml"
services:
  db:
    image: "mysql:8.0"
    volumes:
      - db_data:/var/lib/mysql
    restart: always
    environment:
      MYSQL_DATABASE: solidinvoice
      MYSQL_ALLOW_EMPTY_PASSWORD: 1
  app:
    image: "solidinvoice/solidinvoice:latest"
    depends_on:
      - db
    ports:
      - "8765:8765"
    restart: always
    volumes:
      - app_data:/etc/solidinvoice

volumes:
  db_data: {}
  app_data: {}
```

Bring it up:

```bash
docker compose up -d
```

:::warning
The example above uses an empty MySQL root password for simplicity. Set `MYSQL_ROOT_PASSWORD` and `MYSQL_PASSWORD` (and use `MYSQL_USER`) before running this in production.
:::

## Persisting data

Mount a volume (or bind mount) at `/etc/solidinvoice` so application data survives container restarts and image upgrades:

```bash
docker run -d -p 8765:8765 -v solidinvoice_data:/etc/solidinvoice solidinvoice/solidinvoice
```

## Image source

Pull from [Docker Hub](https://hub.docker.com/r/solidinvoice/solidinvoice).

:::info
Recurring tasks and async work (email sending) run automatically inside the container — no separate cron job or messenger consumer to set up.
:::

## Update

```bash
docker pull solidinvoice/solidinvoice:latest
docker compose up -d   # or `docker stop` + `docker run` again
```
