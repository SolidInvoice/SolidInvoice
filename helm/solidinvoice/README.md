# SolidInvoice Helm Chart

[SolidInvoice](https://github.com/SolidWorx/SolidInvoice) is an open-source invoicing application designed for small businesses and freelancers. It provides client management, quotes, invoices (including recurring), payment processing via Payum, tax and discount handling, a REST API, and notifications.

This Helm chart deploys SolidInvoice on a Kubernetes cluster using the [Helm](https://helm.sh) package manager.

---

## Prerequisites

- Kubernetes 1.23+
- Helm 3.2+
- A StorageClass that supports persistent volume claims (PVC) — required for `/etc/solidinvoice` (Symfony secrets vault and configuration storage)

---

## Installation

### Add required Helm repositories

```bash
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo add meilisearch https://meilisearch.github.io/meilisearch-kubernetes
helm repo update
```

### Update chart dependencies

```bash
helm dep update helm/solidinvoice
```

### Minimal install (external database)

The simplest production-ready install uses an external database provided via a connection URL:

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set externalDatabase.url="mysql://user:password@host:3306/solidinvoice" \
  --set app.secret="your-secret-key"
```

### Install with MySQL subchart

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set mysql.enabled=true \
  --set mysql.auth.password="your-mysql-password" \
  --set mysql.auth.rootPassword="your-root-password" \
  --set app.secret="your-secret-key"
```

### Install with PostgreSQL subchart

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set postgresql.enabled=true \
  --set postgresql.auth.password="your-pg-password" \
  --set app.secret="your-secret-key"
```

### Install with Redis for async messaging

Redis enables the Symfony Messenger async transport, which offloads background tasks (email sending, notification dispatch) from the request cycle:

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set mysql.enabled=true \
  --set mysql.auth.password="your-mysql-password" \
  --set redis.enabled=true \
  --set redis.auth.password="your-redis-password" \
  --set app.secret="your-secret-key"
```

### GitOps / automated install (skip web wizard)

For fully automated deployments, enable the CLI install Job. This pre-install hook runs `solidinvoice:install` before the application starts, skipping the web wizard:

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set mysql.enabled=true \
  --set mysql.auth.password="your-mysql-password" \
  --set app.secret="your-secret-key" \
  --set install.enabled=true \
  --set install.adminEmail="admin@example.com" \
  --set install.adminPassword="your-admin-password"
```

Alternatively, store admin credentials in an existing Secret:

```bash
helm install solidinvoice ./helm/solidinvoice \
  --set mysql.enabled=true \
  --set mysql.auth.password="your-mysql-password" \
  --set app.secret="your-secret-key" \
  --set install.enabled=true \
  --set install.existingSecret="my-admin-secret"
```

---

## Configuration

The following table lists the major configurable parameters. For the full list see `values.yaml`.

| Parameter | Description | Default |
|-----------|-------------|---------|
| `image.repository` | Container image repository | `solidinvoice/solidinvoice` |
| `image.tag` | Image tag (defaults to chart appVersion) | `""` |
| `image.pullPolicy` | Image pull policy | `IfNotPresent` |
| `replicaCount` | Number of app replicas | `1` |
| `app.secret` | `SOLIDINVOICE_APP_SECRET` — auto-generated if empty | `""` |
| `app.locale` | Application locale | `en` |
| `app.allowRegistration` | Allow public user registration | `false` |
| `app.workerMode` | Enable FrankenPHP persistent worker mode | `false` |
| `install.enabled` | Enable CLI install Job (pre-install hook) | `false` |
| `install.adminEmail` | Admin email (required when `install.enabled=true`) | `""` |
| `install.adminPassword` | Admin password (required when `install.enabled=true`) | `""` |
| `install.existingSecret` | Existing Secret with admin credentials | `""` |
| `install.skipUser` | Skip admin user creation | `false` |
| `externalDatabase.url` | Full `DATABASE_URL` connection string | `""` |
| `externalDatabase.existingSecret` | Existing Secret containing `DATABASE_URL` | `""` |
| `mailer.dsn` | Mailer DSN | `null://null` |
| `mailer.sender` | Sender address for outgoing emails | `SolidInvoice <no-reply@example.com>` |
| `mailer.existingSecret` | Existing Secret containing `MAILER_DSN` | `""` |
| `messenger.dsn` | Messenger transport DSN (auto-set when Redis enabled) | `""` |
| `worker.enabled` | Enable Messenger consumer worker Deployment | `true` |
| `worker.replicaCount` | Number of worker replicas | `1` |
| `worker.autoscaling.enabled` | Enable HPA for the worker | `false` |
| `scheduler.enabled` | Enable cron scheduler CronJob | `true` |
| `scheduler.schedule` | Cron schedule expression | `* * * * *` |
| `scheduler.timeZone` | Timezone for the CronJob | `UTC` |
| `persistence.enabled` | Enable PVC for `/etc/solidinvoice` | `true` |
| `persistence.storageClass` | StorageClass name (empty = cluster default) | `""` |
| `persistence.accessModes` | PVC access modes | `[ReadWriteOnce]` |
| `persistence.size` | PVC size | `1Gi` |
| `persistence.existingClaim` | Use an existing PVC | `""` |
| `ingress.enabled` | Enable Ingress | `false` |
| `ingress.className` | Ingress class name | `""` |
| `ingress.hosts` | Ingress host rules | see `values.yaml` |
| `ingress.tls` | Ingress TLS configuration | `[]` |
| `service.type` | Kubernetes Service type | `ClusterIP` |
| `service.port` | Service port | `80` |
| `mysql.enabled` | Enable Bitnami MySQL subchart | `false` |
| `mysql.auth.database` | MySQL database name | `solidinvoice` |
| `mysql.auth.username` | MySQL username | `solidinvoice` |
| `mysql.auth.password` | MySQL password | `""` |
| `postgresql.enabled` | Enable Bitnami PostgreSQL subchart | `false` |
| `postgresql.auth.database` | PostgreSQL database name | `solidinvoice` |
| `postgresql.auth.username` | PostgreSQL username | `solidinvoice` |
| `postgresql.auth.password` | PostgreSQL password | `""` |
| `redis.enabled` | Enable Bitnami Redis subchart | `false` |
| `redis.auth.password` | Redis password | `""` |
| `meilisearch.enabled` | Enable Meilisearch subchart (future feature) | `false` |
| `sentry.enabled` | Enable Sentry error tracking | `false` |
| `sentry.dsn` | Sentry DSN | `""` |
| `oauth.google.enabled` | Enable Google OAuth | `false` |
| `autoscaling.enabled` | Enable HPA for the main app | `false` |
| `autoscaling.minReplicas` | HPA minimum replicas | `1` |
| `autoscaling.maxReplicas` | HPA maximum replicas | `10` |
| `podDisruptionBudget.enabled` | Enable PodDisruptionBudget | `false` |
| `networkPolicy.enabled` | Enable NetworkPolicy | `false` |
| `resources.requests.cpu` | App container CPU request | `200m` |
| `resources.requests.memory` | App container memory request | `256Mi` |
| `resources.limits.cpu` | App container CPU limit | `1000m` |
| `resources.limits.memory` | App container memory limit | `512Mi` |
| `serviceAccount.create` | Create a ServiceAccount | `true` |
| `nodeSelector` | Node selector for pod scheduling | `{}` |
| `tolerations` | Tolerations for pod scheduling | `[]` |
| `affinity` | Affinity rules for pod scheduling | `{}` |
| `extraObjects` | Extra Kubernetes manifests to render | `[]` |

---

## Upgrading

To upgrade the release:

```bash
helm upgrade solidinvoice ./helm/solidinvoice \
  --set app.secret="your-saved-secret-key" \
  --reuse-values
```

Database migrations run automatically as a pre-upgrade Helm hook Job (`migrations` Job). The application pods will not start until migrations complete successfully. The job has a configurable `backoffLimit` (default: 3) and `activeDeadlineSeconds` (default: 300).

**Important:** Always pass `--set app.secret=<saved-value>` on upgrades. If the secret changes, the Symfony encrypted secrets vault becomes unreadable.

---

## Uninstall

```bash
helm uninstall solidinvoice
```

**Note:** The PVC for `/etc/solidinvoice` is annotated with `helm.sh/resource-policy: keep` and will **not** be deleted on uninstall. This prevents accidental data loss of the Symfony secrets vault and configuration.

To delete the PVC manually after uninstall:

```bash
kubectl delete pvc solidinvoice-solidinvoice-config
```

---

## Architecture

SolidInvoice on Kubernetes consists of the following components:

- **App Deployment** — Runs the SolidInvoice web application using [FrankenPHP](https://frankenphp.dev), a modern PHP application server with built-in HTTP/2 and HTTPS support. FrankenPHP can also be run in persistent worker mode (`app.workerMode=true`) for improved performance.

- **Worker Deployment** — Runs the Symfony Messenger consumer (`messenger:consume`). Processes background jobs such as email sending and notification dispatch. Enabled by default (`worker.enabled=true`). Scales independently of the app via HPA.

- **Scheduler CronJob** — Runs `cron:run` on the configured schedule (default: every minute) to trigger recurring invoices, scheduled notifications, and other time-based tasks.

- **Migrations Job** — A pre-upgrade Helm hook that runs Doctrine database migrations before the new application version starts.

- **Install Job** (optional) — A pre-install Helm hook that runs the CLI install command for automated/GitOps deployments.

- **Persistent Volume** — Mounts `/etc/solidinvoice` across all workloads. This directory holds the Symfony encrypted secrets vault and application configuration. It must be shared across all pods.

---

## Links

- [SolidInvoice on GitHub](https://github.com/SolidWorx/SolidInvoice)
- [SolidInvoice Documentation](https://solidinvoice.co/docs)
- [Helm Documentation](https://helm.sh/docs/)
- [Bitnami Charts](https://github.com/bitnami/charts)
