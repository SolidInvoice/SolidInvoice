---
title: Sentry
description: Send SolidInvoice errors, logs, and performance data to Sentry.
sidebar_position: 1
---

# Sentry

SolidInvoice integrates with [Sentry](https://sentry.io/) so you can monitor errors, logs, and performance for your installation. The integration is built in — you only need to provide a DSN to enable it.

## What gets captured

When a DSN is configured, SolidInvoice sends the following to Sentry:

- **Errors** — uncaught exceptions and any log entry at level `ERROR` or higher, buffered using Monolog's *fingers crossed* handler so each error ships with the surrounding context (up to 50 prior log records).
- **Logs** — log records at `INFO` level and above, excluding the `doctrine`, `request`, `security`, `event`, and `console` channels (these are noisy and rarely useful at scale).
- **Performance traces** *(opt-in)* — HTTP requests, console commands, Doctrine SQL queries, Twig renders, Symfony Cache hits/misses, and outgoing HttpClient requests.
- **Profiles** *(opt-in, requires the `excimer` PHP extension)* — CPU profiles for traced requests.

Errors with HTTP status codes `401`, `404`, and `405` are excluded by default to reduce noise.

## Setting up Sentry

1. Create a project in Sentry and copy its [DSN](https://docs.sentry.io/product/sentry-basics/dsn-explainer/).
2. Set the `SOLIDINVOICE_SENTRY_DSN` environment variable for your SolidInvoice instance (see the platform-specific instructions below).
3. Restart the application so the new environment is loaded.

That's all that's required to start receiving errors. Performance tracing and profiling are opt-in and configured separately (see [Performance monitoring](#performance-monitoring)).

### Docker

If you're running SolidInvoice using Docker, pass the DSN as an environment variable:

```bash
docker run -e SOLIDINVOICE_SENTRY_DSN=https://<key>@o0.ingest.sentry.io/<project-id> solidinvoice/solidinvoice
```

### Distribution package

When running SolidInvoice from the distribution package or from source, add the DSN to the `.env` file at the root of the application. Create the file if it doesn't exist:

```ini title=".env"
SOLIDINVOICE_SENTRY_DSN=https://<key>@o0.ingest.sentry.io/<project-id>
```

## Configuration options

All Sentry-related settings are environment variables prefixed with `SOLIDINVOICE_SENTRY_`. Set them the same way you set the DSN.

| Variable | Default | Description |
| --- | --- | --- |
| `SOLIDINVOICE_SENTRY_DSN` | *(empty)* | Your Sentry project DSN. Leave empty to disable the integration. |
| `SOLIDINVOICE_SENTRY_RELEASE` | Application version | Release name used to tag events. Useful for spotting regressions across upgrades. |
| `SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII` | `0` | When `1`, attaches the request user, IP address, and cookies to events. Leave at `0` unless you've reviewed the privacy implications for your users. |
| `SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE` | `0` | Fraction of requests to capture as performance traces, between `0.0` and `1.0`. `0` disables tracing entirely. |
| `SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE` | `0` | Fraction of *traced* requests to also profile. Requires the `excimer` PHP extension. |
| `SOLIDINVOICE_SENTRY_HTTP_TIMEOUT` | `2` | HTTP read timeout (in seconds) for sending events to Sentry. |
| `SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT` | `2` | HTTP connect timeout (in seconds) for sending events to Sentry. |

:::info
`SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII` accepts boolean-ish values — `1`/`0`, `true`/`false`. The sample-rate variables expect a decimal between `0` and `1` (e.g. `0.1` for 10%).
:::

## Performance monitoring

Tracing is wired up but disabled by default. To enable it, set a sample rate above `0`:

```ini title=".env"
SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE=0.1
```

Recommended starting points:

- **Low-traffic / self-hosted single tenant:** `1.0` (capture everything).
- **Medium traffic:** `0.1` (capture 10% of requests).
- **High traffic:** `0.01` (capture 1%).

Once enabled, traces include child spans for every Doctrine query, Twig render, cache lookup, and outbound HttpClient call, plus console commands. Long-running workers (`messenger:consume`, `schedule:run`, `cron:run`) are excluded from tracing — they would otherwise produce a single trace spanning the worker's entire lifetime.

:::tip
Start with a low sample rate in production and raise it only if you need more data. Sentry bills by event volume, and tracing produces far more events than error tracking.
:::

### Profiling

Profiling captures CPU samples for traced requests and requires the [`excimer` PHP extension](https://github.com/wikimedia/php-excimer). To enable:

```ini title=".env"
SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE=0.1
SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE=1.0
```

The profile sample rate is *relative to* the trace sample rate. With the values above, 10% of requests are traced and 100% of those traces are profiled — i.e. 10% of all requests are profiled.

:::warning
If `excimer` is not installed, leave `SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE` at `0`. The static binary distribution of SolidInvoice ships with `excimer` included; if you've built PHP yourself, you'll need to install it via PECL.
:::

## Using a Sentry Relay

[Sentry Relay](https://docs.sentry.io/product/relay/) is a lightweight proxy that buffers events locally and forwards them to Sentry asynchronously. It's worth using when you want predictable latency, scrub sensitive data before it leaves your network, or run SolidInvoice in environments with restricted egress.

To send events through Relay, point the DSN at your Relay instance and keep the default short timeouts:

```ini title=".env"
SOLIDINVOICE_SENTRY_DSN=http://<key>@localhost:3000/<project-id>
SOLIDINVOICE_SENTRY_HTTP_TIMEOUT=2
SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT=2
```

When sending events directly to `sentry.io` (no Relay), consider raising both timeouts to `5`–`10` seconds to tolerate occasional latency.

## Tagging releases

By default, events are tagged with SolidInvoice's application version. If you deploy from source or run a customised build, set `SOLIDINVOICE_SENTRY_RELEASE` to a value that uniquely identifies the deployment — typically a Git SHA or semver tag:

```ini title=".env"
SOLIDINVOICE_SENTRY_RELEASE=3.0.0-rc1
```

This makes it possible to spot regressions introduced by a specific release in Sentry's release health view.

## Verifying the integration

To confirm events are reaching Sentry, send a test event from the command line:

```bash
bin/console sentry:test
```

The command sends a synthetic event using your configured DSN. It should appear in Sentry's *Issues* view within a few seconds.

## Disabling Sentry

Leave `SOLIDINVOICE_SENTRY_DSN` empty (or remove it from `.env`) and restart the application. With no DSN configured, no events are sent, and the integration adds negligible overhead.
