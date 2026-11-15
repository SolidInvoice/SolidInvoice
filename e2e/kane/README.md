# End-to-end (e2e) tests for web

Browser tests written in plain English for [Kane CLI](https://www.testmuai.com/kane-cli/).

| Test | What it covers |
|---|---|
| `install_test.md` | The installation wizard with SQLite, up to the onboarding welcome |
| `invoice_test.md` | Signing in, onboarding a business and client, and creating the first invoice |

## Run

Start a fresh instance on port 4920:

```bash
docker run -d --name solidinvoice-e2e -p 4920:8765 solidinvoice/solidinvoice:latest
npm install -g @testmuai/kane-cli
kane-cli login
kane-cli testmd run e2e/kane/install_test.md
```

`invoice_test.md` needs an installed instance. On a fresh container, run:

```bash
docker exec solidinvoice-e2e solidinvoice console solidinvoice:install -n --database-driver=pdo_sqlite --admin-email=alex@example.com --admin-password='Solid-Test-2026!' --locale=en_US --application-url=http://localhost:4920 --disable-telemetry
docker restart solidinvoice-e2e
kane-cli testmd run e2e/kane/invoice_test.md
```

Recreate the container before each run.
