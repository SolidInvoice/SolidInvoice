# Sentry

SolidInvoice provides integration with [Sentry](https://sentry.io/), where you can log any errors while running the application.



## Setting Up Sentry

To set up the Sentry integration, you need to set up an `SENTRY_DSN` environment variable which contains the [DSN of your Sentry account](https://docs.sentry.io/product/sentry-basics/dsn-explainer/)

### Docker

If you are running SolidInvoice using Docker, then you can add the environment variable when starting the application:

```bash
docker run -e SENTRY_DSN=... solidinvoice/solidinvoice
```

### Distribution Package

When running SolidInvoice using the distribution package, you can add the environment variable to a `.env` file in the root of the application.

If your application does not have a `.env` at the root directory, then you can create the file and add the Sentry DSN environment variable to the file:

```
SENTRY_DSN=...
```

