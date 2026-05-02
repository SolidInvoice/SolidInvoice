<div align="center">

<img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/6f45c11d-d73e-423e-be4a-30cdf2fe819d" alt="SolidInvoice" width="100%" />

# SolidInvoice

**The open-source invoicing platform for freelancers and small businesses.**

Send beautiful quotes and invoices, accept online payments, automate recurring billing — and own every byte of your data.

<p>
  <a href="https://github.com/SolidInvoice/SolidInvoice/blob/3.0.x/LICENSE"><img alt="License: MIT" src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" /></a>
  <a href="https://github.com/SolidInvoice/SolidInvoice/releases"><img alt="Latest Release" src="https://img.shields.io/github/v/release/SolidInvoice/SolidInvoice?include_prereleases&style=flat-square" /></a>
  <a href="https://www.php.net/"><img alt="PHP 8.4+" src="https://img.shields.io/badge/php-8.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" /></a>
  <a href="https://symfony.com/"><img alt="Symfony 7" src="https://img.shields.io/badge/symfony-7.1-000000?style=flat-square&logo=symfony" /></a>
  <a href="https://hub.docker.com/r/solidinvoice/solidinvoice"><img alt="Docker Pulls" src="https://img.shields.io/docker/pulls/solidinvoice/solidinvoice?style=flat-square&logo=docker&logoColor=white" /></a>
  <a href="https://github.com/SolidInvoice/SolidInvoice/stargazers"><img alt="GitHub Stars" src="https://img.shields.io/github/stars/SolidInvoice/SolidInvoice?style=flat-square" /></a>
</p>

<p>
  <a href="https://solidinvoice.co"><img src="https://img.shields.io/badge/Try%20Hosted-%248%2Fmo-2ea44f?style=for-the-badge" alt="Try Hosted" /></a>
  <a href="https://hub.docker.com/r/solidinvoice/solidinvoice"><img src="https://img.shields.io/badge/Self--Host-Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Self-Host with Docker" /></a>
  <a href="https://github.com/SolidInvoice/SolidInvoice"><img src="https://img.shields.io/badge/Star-on%20GitHub-181717?style=for-the-badge&logo=github" alt="Star on GitHub" /></a>
</p>

<img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/efdb4dc1-5b5f-4fa6-a90e-bd6d1bac186a" alt="SolidInvoice Dashboard" width="100%" />

</div>

---

## Why SolidInvoice?

Most invoicing tools force a trade-off: easy to use *or* respectful of your data. SolidInvoice gives you both. It's a mature, production-ready billing platform you can run on your own server for free, or let us host for a flat **$8/month** — no per-client limits, no surprise tiers, no lock-in. Built on Symfony 7 and PHP 8.4, it's designed to be extended, integrated, and trusted.

---

## ✨ Features

### 💼 Billing & Invoicing
- Quotes that convert into invoices in one click
- Recurring invoices on flexible schedules
- Multi-currency support (real `Money` objects — no float rounding)
- Tax rules and discounts (percentage or fixed amount)
- Branded PDF generation
- Invoice state machine (draft → pending → paid)

### 👥 Clients & Contacts
- Full client and contact management
- Per-client currency, addresses and contact channels
- Multi-tenancy out of the box (run multiple companies from one install)

### 💳 Payments
- Bring-your-own Stripe, PayPal and other gateways via [Payum](https://payum.gitbook.io/payum/)
- Online payment links sent with invoices
- PCI-compliant — no card data ever touches your server

### 🔌 Integrations & API
- REST API (JSON-LD, JSON-HAL, JSON, XML) powered by [API Platform 4](https://api-platform.com/)
- Token-based auth (`X-API-TOKEN`)
- Built-in MCP server for AI agent automation
- Notifications via email, SMS and chat channels

### 🛡 Privacy & Ownership
- 100% self-hostable — your database, your rules
- Role-based access control with Symfony Security & Voters
- Encrypted secrets, Doctrine multi-tenancy filters
- MIT licensed — fork it, modify it, ship it

### 🚀 Modern Stack
- Symfony 7.1, PHP 8.4, Doctrine ORM, API Platform 4
- Tabler UI on Bootstrap 5.3, Stimulus, Webpack Encore, Bun
- ULID primary keys, PHPStan level 6, ECS, Rector

---

## 🏠 Self-Hosted vs. ☁️ Hosted

Both versions ship the same codebase and feature set. Pick whichever fits your workflow.

|                              | 🏠 **Self-Hosted** (Free, MIT) | ☁️ **Hosted** ($8/month)         |
| ---------------------------- | ------------------------------ | --------------------------------- |
| Price                        | Free forever                   | Flat $8/mo — no per-client fees   |
| Setup                        | You install & maintain         | Zero setup — sign up and send     |
| Updates                      | Manual                         | Automatic                         |
| Backups                      | You manage                     | Daily, managed for you            |
| Branding                     | Yours                          | SolidInvoice branding removed     |
| Early access to new features | —                              | ✅                                |
| Data ownership               | Full                           | Full — export anytime             |
| Best for                     | Tinkerers, privacy-first teams | Anyone who wants to invoice today |

<p align="center">
  <a href="https://solidinvoice.co"><b>Start with the hosted version → solidinvoice.co</b></a>
</p>

---

## 📸 Screenshots

| | |
| :---: | :---: |
| <img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/efdb4dc1-5b5f-4fa6-a90e-bd6d1bac186a" alt="Dashboard" /><br/>**Dashboard** | <img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/b89c1239-2455-48ef-9ee0-47b78cf69483" alt="Client View" /><br/>**Client View** |
| <img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/a04e2aad-ee98-4615-9096-e16d647534f5" alt="Invoice Editor" /><br/>**Invoice Editor** | <img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/bbd16da6-61ed-4b20-8a12-f78b1a20c39f" alt="Payments" /><br/>**Payments** |
| <img src="https://github.com/SolidInvoice/SolidInvoice/assets/144858/fcc7e26e-6c58-4706-9891-1b00df371873" alt="Settings" /><br/>**Settings** | |

---

## 🚀 Quick Start

### Option 1 — Hosted (recommended)

The fastest way to start sending invoices. Sign up at **[solidinvoice.co](https://solidinvoice.co)** — no install, no server, automatic updates and backups for $8/month.

### Option 2 — Docker

```bash
docker run -p 8080:80 solidinvoice/solidinvoice
```

Full image and configuration options: **[hub.docker.com/r/solidinvoice/solidinvoice](https://hub.docker.com/r/solidinvoice/solidinvoice)**

### Option 3 — Single binary or Homebrew

Get up and running in seconds with a self-contained binary — no PHP, no web server, no extensions to install.

**macOS / Linux (Homebrew):**

```bash
brew install solidworx/tap/solidinvoice
solidinvoice run
```

**Direct binary download:**

Grab the latest binary for your platform from the [releases page](https://github.com/SolidInvoice/SolidInvoice/releases), make it executable, and run it:

```bash
chmod +x solidinvoice
./solidinvoice run
```

That's it — open `http://localhost:8765` and you're invoicing.

### Option 4 — From source (for developers)

```bash
git clone https://github.com/SolidInvoice/SolidInvoice.git
cd SolidInvoice
composer install
bun install && bun run dev
```

For production builds:

```bash
bun run build
```

**Requirements:** PHP 8.4+, ext-curl, ext-gd, ext-intl, ext-openssl, ext-pdo, ext-soap, ext-xsl, MySQL/MariaDB or PostgreSQL.

---

## 🛠 Tech Stack

**Backend:** Symfony 7.1 · PHP 8.4 · Doctrine ORM · API Platform 4 · Payum · MoneyPHP
**Frontend:** Tabler · Bootstrap 5.3 · Stimulus · Webpack Encore · Bun · Sass
**Quality:** PHPStan (level 6) · ECS · Rector · PHPUnit · Foundry · GitHub Actions

---

## 📚 Documentation

- 🌐 Website — [solidinvoice.co](https://solidinvoice.co)
- 📖 Docs & guides — [`/docs`](docs/)
- 🔄 Upgrading — [`UPGRADE.md`](UPGRADE.md)
- 📝 Changelog — [`CHANGELOG.md`](CHANGELOG.md)

---

## 🤝 Contributing

We love contributions of every shape — code, docs, translations, bug reports, ideas. Look for the [`good first issue`](https://github.com/SolidInvoice/SolidInvoice/labels/good%20first%20issue) label to get started, then read the [contributing guide](CONTRIBUTING.md) and our [code of conduct](CODE_OF_CONDUCT.md).

---

## 🔒 Security

Found a vulnerability? Please **do not** open a public issue. See [`SECURITY.md`](SECURITY.md) for our responsible disclosure process.

---

## 💖 Sponsors & Acknowledgements

SolidInvoice is built and maintained thanks to our sponsors. Want to support the project? **[Become a sponsor](https://github.com/sponsors/SolidInvoice?o=esc)**.

A huge thank-you to:

- **[JetBrains](https://www.jetbrains.com/)** — PhpStorm licenses
- **[Docker](https://www.docker.com/)** — Docker Hub subscription
- **[Sentry](https://sentry.io/)** — Sponsored Business plan

---

## 📄 License

SolidInvoice is open-source software released under the [MIT License](LICENSE).

---

<div align="center">

**[Website](https://solidinvoice.co)** · **[Hosted](https://solidinvoice.co)** · **[Docs](docs/)** · **[Releases](https://github.com/SolidInvoice/SolidInvoice/releases)** · **[Sponsor](https://github.com/sponsors/SolidInvoice?o=esc)**

Made with ❤️ by [SolidWorx](https://solidworx.co) and [contributors](https://github.com/SolidInvoice/SolidInvoice/graphs/contributors).

</div>

<!-- GitAds-Verify: 5A777YN6A52PDTET1VL1VHZGIO89ZZT5 -->
