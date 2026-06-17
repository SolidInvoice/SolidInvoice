---
title: Prometheus metrics
description: Expose Caddy HTTP and FrankenPHP worker metrics for Prometheus scraping.
sidebar_position: 4
---

# Prometheus metrics

When running the SolidInvoice [distribution package](../installation-guide/distribution-package/index.mdx) (single binary), you can expose a Prometheus-compatible metrics endpoint that reports Caddy HTTP metrics and FrankenPHP worker and thread statistics.

:::info
Prometheus metrics are only available with the distribution package (the `solidinvoice` single binary). Docker and Helm deployments do not expose this endpoint by default.
:::

## Enable metrics

Pass `--enable-metrics` to the `run` command:

```bash
solidinvoice run --enable-metrics
```

The metrics endpoint starts on port **9090** by default. SolidInvoice prints a note at startup confirming the address:

```
Metrics: Prometheus metrics available at http://localhost:9090/metrics
```

## Change the metrics port

Use `--metrics-port` to listen on a different port:

```bash
solidinvoice run --enable-metrics --metrics-port 9100
```

## Configure Prometheus to scrape

Add a scrape job to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: solidinvoice
    static_configs:
      - targets:
          - localhost:9090
```

Replace `localhost` with the host or IP where SolidInvoice is running, and adjust the port if you used `--metrics-port`.

## What's exposed

The `/metrics` endpoint reports standard Caddy HTTP server metrics (request counts, response sizes, latencies by status code and route) and FrankenPHP worker and thread pool statistics (active workers, idle threads, PHP execution times).

## Related

- [Distribution package installation](../installation-guide/distribution-package/index.mdx)
- [Sentry integration](./sentry.md)
