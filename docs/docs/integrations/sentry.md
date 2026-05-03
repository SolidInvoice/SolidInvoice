---
title: Sentry
description: Send SolidInvoice errors to Sentry.
sidebar_position: 1
---

# Sentry

SolidInvoice provides integration with [Sentry](https://sentry.io/), where you can log any errors while running the application.

## Setting up Sentry

To set up the Sentry integration, you need to set the `SENTRY_DSN` environment variable to the [DSN of your Sentry account](https://docs.sentry.io/product/sentry-basics/dsn-explainer/).

### Docker

If you're running SolidInvoice using Docker, add the environment variable when starting the application:

```bash
docker run -e SENTRY_DSN=... solidinvoice/solidinvoice
```

### Distribution package

When running SolidInvoice using the distribution package, add the environment variable to a `.env` file in the root of the application.

If your application doesn't have a `.env` file at the root directory, create one and add the Sentry DSN environment variable:

```ini
SENTRY_DSN=...
```
