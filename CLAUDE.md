# SolidInvoice - AI Assistant Guide

Open-source invoicing application for small businesses/freelancers. Features: client management, quotes, invoices (recurring), payments (Payum), tax/discounts, REST API, notifications.

**Version:** 3.0.0-dev | **License:** MIT | **Author:** Pierre du Plessis

**Current Status:** UI rewrite from AdminLTE (Bootstrap 4) to Tabler (Bootstrap 5.3). All frontend changes must follow Tabler design system.

---

## Technology Stack

**Backend:** Symfony 7.1+, PHP 8.4+, Doctrine ORM, API Platform 4.0+, payum/payum-bundle, moneyphp/money

**Frontend:** Webpack (Encore), Stimulus, Tabler (Bootstrap 5.3), Sass, Bun

**Tools:** PHPStan (Level 6), ECS, Rector, PHPUnit, Foundry, GitHub Actions

**Platform:** Built on SolidWorx/Platform for base entities, services, and UI components.

---

## Codebase Structure

```
├── assets/          # Frontend (Stimulus controllers, SCSS)
├── bin/             # Executables (console, phpunit)
├── config/          # Symfony config (packages/, routes/, services.php)
├── migrations/      # Database migrations
├── src/             # 20 bundles (see below)
├── templates/       # Twig template overrides
├── tests/           # Test bootstrap
```

### Bundles (src/)

**Business:** InvoiceBundle, QuoteBundle, ClientBundle, PaymentBundle, TaxBundle

**User/Security:** UserBundle

**System:** CoreBundle, ApiBundle, MailerBundle, SettingsBundle, InstallBundle, CronBundle

**UI:** DashboardBundle, DataGridBundle, FormBundle, MenuBundle

**Specialized:** NotificationBundle, MoneyBundle, SaasBundle, McpBundle

### Bundle Structure

```
BundleNameBundle/
├── Action/          # HTTP entry points (not controllers)
├── Entity/          # Doctrine entities
├── Form/            # Form types
├── Listener/        # Event listeners
├── Manager/         # Business logic
├── Repository/      # Data access
├── Resources/       # config/, translations/, views/
├── Tests/           # Tests (Functional/ subdirectory for functional)
```

---

## Architecture Patterns

### Action Pattern

HTTP entry points are single-responsibility Action classes, not controllers:

```php
class CreateAction {
    public function __invoke(Request $request): Response { /* ... */ }
}
```

### Key Patterns

- **Repository Pattern:** Extend `SolidWorx\Platform\PlatformBundle\Repository\EntityRepository`
- **Event-Driven:** Symfony EventDispatcher for state transitions, lifecycle
- **Manager Pattern:** Complex operations in Manager classes
- **State Machine:** Symfony Workflow for invoice states (draft, pending, paid, etc.)

### Entity Traits

Use these traits on entities as needed:

- `Archivable` - Soft deletes
- `TimeStampable` - created/updated timestamps
- `CompanyAware` - Multi-tenancy

### Platform

- Repositories extend `SolidWorx\Platform\PlatformBundle\Repository\EntityRepository`
- Commands extend `SolidWorx\Platform\PlatformBundle\Console\Command`
- Use SolidWorx/PlatformUI components for UI

---

## Development Commands

```bash
# Frontend
bun install && bun run dev   # Setup & build
bun run build                # Production build

# Backend
bin/console cache:clear
bin/console doctrine:migrations:migrate

# Quality (see .claude/skills/code-quality.md)
bin/ecs check --fix
bin/phpstan analyse
bin/phpunit
bin/rector process --dry-run  # Preview refactoring suggestions
```

---

## Key Conventions

### Naming

| Type       | Pattern           | Example                      |
|------------|-------------------|------------------------------|
| Entity     | Singular          | `Invoice`, `Client`          |
| Repository | Entity+Repository | `InvoiceRepository`          |
| Form       | Name+Type         | `InvoiceType`                |
| Action     | Verb              | `CreateAction`, `EditAction` |
| Manager    | Entity+Manager    | `InvoiceManager`             |

### PHP Standards

- Always `declare(strict_types=1);`
- Always type hints (params + returns)
- Prefer `final` classes — **exception: Doctrine entities must never be `final`**, as Doctrine generates proxy classes by extending them at runtime
- Use PHP 8.1+ backed enums for fixed sets of values (status, type, etc.), NEVER class constants
- File header required (see .claude/skills/code-quality.md)

### Doctrine

- PHP 8 Attributes for mapping
- ULID primary keys
- Global filters: `CompanyFilter` (multi-tenancy), `ArchivableFilter` (soft deletes)

### Migrations

Location: `/migrations/`

- Use Doctrine Schema tool, not raw SQL
- Naming: `Version{major}{minor}{patch}.php` (e.g., `Version203011.php`)
- From 2.4: `Version{version}_{part}.php` (e.g., `Version20400_1.php`)

---

## Database

ULID primary keys:

```php
#[ORM\Column(type: UlidType::NAME)]
#[ORM\Id]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UlidGenerator::class)]
private Ulid $id;
```

---

## API

API Platform 4.0+. Auth: `X-API-TOKEN` header.

Endpoints: `/api/invoices`, `/api/quotes`, `/api/clients`, `/api/contacts`, `/api/payments`, `/api/tax`

Formats: JSON-LD (default), JSON-HAL, JSON, XML

Custom normalizers: `MoneyNormalizer`, `CreditNormalizer`, `DiscountNormalizer`

---

## Money Handling

Always use Money objects, never floats:

```php
$amount = Money::USD(1000); // $10.00 (cents)
```

Currencies come from Client entity. Never use default currency.

---

## Security

- Multi-tenancy via CompanyFilter (all company-aware entities use `CompanyAware` trait)
- RBAC with Symfony Security + Voters
- PCI-compliant payments via Payum
- Use env vars for secrets

---

## Quick Reference

### Adding Features

1. Identify/create appropriate bundle
2. Follow bundle structure (Action, Entity, Repository, etc.)
3. Add type hints, strict types, file header
4. Write tests
5. Run `bin/ecs check --fix && bin/phpstan analyse && bin/phpunit`
6. Generate migrations if DB changes

### Fixing Bugs

1. Add failing test
2. Fix bug
3. Verify test passes
4. Run full suite + static analysis

### Code Review Checklist

- Strict types, type hints, file header
- Tests included
- No hardcoded values
- No security vulnerabilities
- Passes CI checks

---

## Skills Reference

Detailed guides extracted to skills:

- **`.claude/skills/design-system.md`** - UI components, tokens, patterns
- **`.claude/skills/code-quality.md`** - ECS, PHPStan, Rector, standards
- **`.claude/skills/testing.md`** - PHPUnit, test types, fixtures

---

## External Docs

- [Symfony 7.1](https://symfony.com/doc/7.1/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [API Platform](https://api-platform.com/docs/symfony/)
- [Payum](https://payum.gitbook.io/payum/)
