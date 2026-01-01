# SolidInvoice - AI Assistant Guide

This document provides comprehensive guidance for AI assistants working with the SolidInvoice codebase. It covers the architecture, conventions, workflows, and best practices to help you understand and effectively contribute to this project.

## Table of Contents

- [Project Overview](#project-overview)
- [Technology Stack](#technology-stack)
- [Codebase Structure](#codebase-structure)
- [Architecture & Design Patterns](#architecture--design-patterns)
- [Development Workflow](#development-workflow)
- [Testing Strategy](#testing-strategy)
- [Code Quality & Standards](#code-quality--standards)
- [Common Tasks & Commands](#common-tasks--commands)
- [Key Conventions](#key-conventions)
- [Database & ORM](#database--orm)
- [API Development](#api-development)
- [Frontend Development](#frontend-development)
- [Security Considerations](#security-considerations)

---

## Project Overview

**SolidInvoice** is a sophisticated open-source invoicing application designed for small businesses and freelancers. It provides comprehensive billing operations including:

- Client and contact management
- Quote creation and management
- Invoice generation and tracking (including recurring invoices)
- Online payment processing (multiple gateways via Payum)
- Tax and discount handling
- RESTful API for integrations
- Multi-channel notifications (email, SMS, webhooks)

**Current Version:** 2.3.11
**License:** MIT
**Primary Author:** Pierre du Plessis

### Current Status

The current branch is in the process of a UI rewrite from AdminLTE (Bootstrap 4) to Tabler (Bootstrap 5.3).
Any changes to templates or frontend code should be made in accordance with the new Tabler design system.

---

## Technology Stack

### Backend

| Component          | Technology              | Version                |
|--------------------|-------------------------|------------------------|
| Framework          | Symfony                 | 7.1+                   |
| Platform           | SolidWorx/Platform      | dev-main               |
| PHP                | PHP                     | 8.4+                   |
| ORM                | Doctrine ORM            | 2.15+                  |
| Migrations         | Doctrine Migrations     | 3.5+                   |
| Database           | MySQL/PostgreSQL/SQLite | MySQL 8.0+ recommended |
| API Framework      | API Platform            | 4.0+                   |
| PDF Generation     | mPDF                    | 8.0+                   |
| Money Handling     | moneyphp/money          | 4.5+                   |
| Task Scheduling    | Symfony Schedule        | 7.1+                   |
| Payment Processing | Payum                   | 1.7+                   |
| Workflow           | Symfony Workflow        | 7.1+                   |

### Frontend

| Component            | Technology               | Version |
|----------------------|--------------------------|---------|
| Build Tool           | Webpack (Symfony Encore) | 5.3+    |
| CSS Preprocessor     | Sass                     | 1.63+   |
| JavaScript Framework | Stimulus                 | 3.2+    |
| UI Framework         | Tabler (Bootstrap)       | 1.4+    |
| Icons                | FontAwesome              | 6.4+    |
| Package Manager      | Bun                      | 1.3+    |

### Development Tools

- **Static Analysis:** PHPStan (Level 6)
- **Coding Standards:** EasyCodingStandard (Symplify)
- **Code Refactoring:** Rector (PHP 8.4+)
- **Testing:** PHPUnit 10.4+, Mockery, Symfony Panther
- **Fixtures:** Foundry
- **CI/CD:** GitHub Actions

### Distribution

- **Docker:** Official SolidInvoice Docker images available on Docker Hub (solidinvoice/solidinvoice). Runs the FrankenPHP binary inside the container.
- **FrankenPHP:** Official recommended installation method. Application is built into a single binary, which contains PHP, all required extensions, the web server, and the application code.
- **Archive:** ZIP/TAR archives for manual installation (all assets pre-compiled)

---

## Codebase Structure

### Root Directory Layout

```text
/home/user/SolidInvoice/
├── assets/                 # Frontend JavaScript and SCSS
│   ├── controllers/        # Stimulus controllers
│   ├── img/                # Images and icons
│   ├── scss/               # Sass stylesheets
│   ├── controllers.json    # Stimulus config
│   └── core.ts             # Main application entry
├── bin/                    # Executables (console, phpunit, etc.)
├── config/                 # Symfony configuration
│   ├── packages/           # Package configurations
│   ├── routes/             # Route definitions
│   └── services.php        # Service container config
├── docker/                 # Docker configuration
├── frankenphp/             # FrankenPHP code and config (Go files and shell scripts to build and compile the binary)
├── migrations/             # Database migrations
├── public/                 # Web-accessible files
├── scripts/                # Shell scripts for various tasks
├── src/                    # Application source code (18 bundles)
├── templates/              # Twig templates which overrides external bundle templates
├── tests/                  # Test bootstrap and utilities
├── var/                    # Runtime data (cache, logs)
├── vendor/                 # Composer dependencies
```

### Bundle Organization (src/)

SolidInvoice uses a modular bundle architecture with 18 bundles:

#### Business Logic Bundles

- **InvoiceBundle** - Invoice management, recurring invoices, cloning, state transitions
- **QuoteBundle** - Quote creation, management, conversion to invoices
- **ClientBundle** - Client and contact management with address tracking
- **PaymentBundle** - Payment processing with Payum integration
- **TaxBundle** - Tax rate management and calculations

#### User & Security

- **UserBundle** - User management, API tokens, authentication

#### System & Infrastructure

- **CoreBundle** - Core utilities, entities, billing logic, PDF generation, Twig extensions
- **ApiBundle** - REST API implementation with API Platform
- **MailerBundle** - Email configuration and delivery
- **SettingsBundle** - Application configuration management
- **InstallBundle** - Installation wizard and setup
- **CronBundle** - Scheduled task management

#### UI & Presentation

- **DashboardBundle** - Dashboard widgets and analytics
- **DataGridBundle** - Data grid/table rendering
- **FormBundle** - Form customizations

#### Specialized Services

- **NotificationBundle** - Multi-channel notifications (email, SMS, webhooks)
- **MoneyBundle** - Money type handling with moneyphp/money library
- **SaasBundle** - SaaS features like subscriptions and usage tracking

### Bundle Internal Structure

Each bundle follows this pattern:

```text
BundleNameBundle/
├── Action/                      # Action classes (entry points for HTTP requests)
├── Entity/                      # Doctrine ORM entities
├── Form/                        # Symfony Form types and handlers
├── (Listener|EventSubscriber)/  # Event listeners and subscribers
├── Manager/                     # Complex business logic managers
├── Repository/                  # Database access objects
├── Resources/                   # Configuration, templates, assets
│   ├── config/                  # Bundle-specific configs
│   │   ├── routing/             # Route configuration      
│   │   └── services/            # DI container services
│   ├── translations/            # Translation files
│   └── views/                   # Twig templates
├── Tests/                       # Unit and functional tests
│   ├── Functional/              # Functional tests
│   └── Unit/                    # Unit tests (not in a `Unit` directory, but just in the top-level Tests directory)
└── BundleNameBundle.php
```

---

## Architecture & Design Patterns

### Core Patterns

#### 1. Modular Bundle Architecture

- Each feature has its own bundle with clear boundaries
- Bundles communicate via events, not direct coupling
- Dependency injection for all services

#### 2. Action Pattern (Not Controllers)

- Entry points for HTTP requests are Action classes
- Single responsibility per action
- Located in `Action/` directory of each bundle

Example:

```php
// src/InvoiceBundle/Action/CreateAction.php
class CreateAction
{
    public function __invoke(Request $request): Response
    {
        // Handle invoice creation
    }
}
```

#### 3. Repository Pattern

- Custom repositories extending Doctrine EntityRepository
- Query optimization and complex business logic isolation
- Located in `Repository/` directory

#### 4. Event-Driven Architecture

- Extensive use of Symfony EventDispatcher
- Events for state transitions, entity lifecycle
- Listeners in `Listener/` or `EventSubscriber/` directory

Key events:

- `InvoiceEvent` - Invoice lifecycle events
- `PaymentCompleteListener` - Payment completion handling
- Doctrine lifecycle callbacks

#### 5. Manager Pattern

- Complex domain operations encapsulated in Manager classes
- Examples: `InvoiceManager`, `PaymentSettingsManager`
- Located in `Manager/` directory

#### 6. State Machine (Workflow)

- Symfony Workflow for invoice state management
- States: draft, pending, recurring, paid, sent, viewed, cancelled
- Graph-based workflow configuration

#### 7. Platform

This application is built using SolidWorx/Platform, which provides foundational services, base entities, and utilities to streamline development.
SolidWorx/Platform is built in conjunction with SolidInvoice and is designed to be reusable across multiple projects.
For any UI functionality, use the SolidWorx/PlatformUI package which provides Twig templates and components for common styles.
In cases where a specific UI component is not available in PlatformUI, mention what is needed so that it can be added to the PlatformUI package for future use.

- All repositories should extend from `SolidWorx\Platform\PlatformBundle\Repository\EntityRepository`.
- All commands should extend from `SolidWorx\Platform\PlatformBundle\Console\Command`.

### Entity Traits (Cross-Cutting Concerns)

Common traits used across entities:

```php
// Can archive entities
trait Archivable {
    private bool $archived = false;
}

// Automatic timestamps
trait TimeStampable {
    private DateTimeInterface $created;
    private DateTimeInterface $updated;
}

// Multi-tenancy
trait CompanyAware {
    private Company $company;
}

// Money value objects
trait Money {
    // Money object fields
}
```

### Dependency Injection

- All services use constructor injection
- Configuration in `config/services.php` and bundle-specific configs
- Auto-wiring enabled for most services

---

## Development Workflow

### Initial Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/SolidInvoice/SolidInvoice.git
   cd SolidInvoice
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install frontend dependencies:**
   ```bash
   bun install
   ```

4. **Build frontend assets:**

   ```bash
   bun run dev  # Development mode
   # or
   bun run build  # Production build
   ```

5. **Setup database:**

   ```bash
   bin/console doctrine:database:create
   bin/console doctrine:migrations:migrate
   ```

### Development Commands

```bash
# Frontend development
bun run dev      # Build assets in dev mode with watch
bun run build    # Production optimized build
bun run lint:js  # ESLint JavaScript validation
bun run lint:css # StyleLint CSS validation

# Backend development
bin/console cache:clear                 # Clear cache
bin/console doctrine:migrations:migrate # Run migrations
bin/console doctrine:schema:validate    # Validate database schema

# Code quality
bin/ecs check                # Check coding standards
bin/ecs check --fix          # Fix coding standards
bin/phpstan analyse          # Run static analysis
bin/rector process --dry-run # Preview refactoring changes
bin/rector process           # Apply refactoring changes

# Testing
bin/phpunit                          # Run all tests
bin/phpunit --coverage-html coverage # Generate coverage report

```

### Git Workflow

1. **Branch naming:** Feature branches should be descriptive
2. **Commit messages:** Follow conventional commits style
3. **Pull requests:** Must pass all CI checks (tests, CS, static analysis)

### CI/CD Pipeline (GitHub Actions)

Every pull request triggers:

1. **Unit Tests** (`unit-tests.yml`)
    - PHP 8.4 and 8.5
    - MySQL 8.0
    - Code coverage reporting (Codecov)
    - E2E tests with Symfony Panther

2. **Coding Standards** (`cs.yml`)
    - EasyCodingStandard (PHP)
    - Composer normalize
    - Super-Linter (YAML, JSON, XML, Markdown, CSS, JavaScript)

3. **Static Analysis** (`static-analysis.yml`)
    - PHPStan (Level 6)
    - Qodana code quality

4. **Security Checks** (`security-checker.yml`)
    - Composer security vulnerabilities
    - CodeQL analysis

---

## Testing Strategy

### Test Structure

```text
src/BundleNameBundle/Tests/
├── Functional/
│   ├── Api/           # API endpoint tests
│   └── ...
├── Form/          # Form type tests
├── Repository/    # Repository tests
└── ...
```

### PHPUnit Configuration

Key settings from `phpunit.xml.dist`:

- **Execution:** Random order (`executionOrder="random"`)
- **Strict mode:** All warnings treated as failures
- **Database isolation:** DAMA/DoctrineTestBundle for transaction wrapping
- **E2E testing:** Symfony Panther for browser testing
- **Environment:** Test environment with `.env.test`

### Test Types

#### 1. Unit Tests

- Test individual classes in isolation
- Use Mockery for mocking dependencies
- Fast execution, no database required

```php
use Mockery as m;

class InvoiceManagerTest extends TestCase
{
    public function testCreateInvoice(): void
    {
        $repository = m::mock(InvoiceRepository::class);
        // Test logic
    }
}
```

#### 2. Functional Tests

- Test full request/response cycle
- Use database for realistic scenarios
- Located in `Tests/Functional/`

#### 3. API Tests

- Test REST API endpoints
- Extend `ApiTestCase` base class
- JSON-LD/HAL response validation

### Fixtures

**Foundry** - Factory-based fixtures

### Running Tests

```bash
# All tests
bin/phpunit

# Specific bundle
bin/phpunit src/InvoiceBundle/Tests

# Specific test file
bin/phpunit src/InvoiceBundle/Tests/Unit/Manager/InvoiceManagerTest.php

# With coverage
bin/phpunit --coverage-html coverage

# Filter by test name
bin/phpunit --filter testCreateInvoice
```

---

## Code Quality & Standards

### Coding Standards (ECS)

Configuration: `ecs.php`

**Standards Applied:**

- PSR-12
- Symfony coding standards
- PHPUnit standards
- Clean code principles

**File Header Required:**

```php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
```

**Commands:**

```bash
bin/ecs check           # Check standards
bin/ecs check --fix     # Auto-fix issues
```

### Static Analysis (PHPStan)

Configuration: `phpstan.neon`

**Settings:**

- Level: 6
- Symfony container XML analysis
- Doctrine plugin enabled
- Baseline: `phpstan-baseline.neon`

**Commands:**

```bash
bin/phpstan analyse                    # Analyze with baseline
bin/phpstan analyse --no-baseline     # Analyze without baseline
```

### Code Refactoring (Rector)

Configuration: `rector.php`

**Rules Applied:**

- PHP upgrades
- Symfony best practices
- Doctrine improvements
- PHPUnit modernization
- Code quality improvements

**Commands:**

```bash
bin/rector process --dry-run    # Preview changes
bin/rector process              # Apply changes
```

### Pre-commit Checklist

Before committing code:

1. ✅ Run coding standards: `bin/ecs check --fix`
2. ✅ Run static analysis: `bin/phpstan analyse`
3. ✅ Run tests: `bin/phpunit`
4. ✅ Ensure proper file headers
5. ✅ Ensure strict types declaration: `declare(strict_types=1);`

---

## Common Tasks & Commands

### Creating a New Entity

1. Use Symfony Maker (if available):
   ```bash
   bin/console make:entity
   ```

2. Or manually create in appropriate bundle's `Entity/` directory

3. Add common traits as needed:
   ```php
   use SolidInvoice\CoreBundle\Traits\Entity\Archivable;
   use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
   use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
   ```

4. Generate migration:
   ```bash
   bin/console doctrine:migrations:diff
   ```

5. Run migration:
   ```bash
   bin/console doctrine:migrations:migrate
   ```

### Creating a New API Endpoint

1. Add `#[ApiResource]` attribute to entity:
   ```php
   use ApiPlatform\Core\Annotation\ApiResource;

   #[ApiResource]
   class MyEntity
   {
       // ...
   }
   ```

2. Configure serialization groups if needed
3. Test the endpoint at `/api/my-entities`

### Adding a New Event Listener

1. Create listener class in `Listener/` directory
2. Implement the listener logic
3. Use auto-configuration to register the entity
4. Tag with appropriate event name if not using auto-configuration

### Working with Money Values

Always use Money objects, never raw floats:

```php
use Money\Money;
use Money\Currency;

$amount = Money::USD(1000); // $10.00 (amounts in cents)
$currency = new Currency('USD');
$price = new Money(5000, $currency); // $50.00
```

Currencies are configured on the `Client` entity, and should always be used for formatting.
Never use a default currency.

### PDF Generation

Use the PDF manager service:

```php
use SolidInvoice\CoreBundle\Pdf\Generator;

$generator->generate($html, $filename);
```

### Sending Emails

Use Symfony Mailer:

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

$email = (new Email())
    ->to($recipient)
    ->subject('Invoice #1234')
    ->html($content);

$mailer->send($email);
```

---

## Key Conventions

### Naming Conventions

| Type         | Convention               | Example                                     |
|--------------|--------------------------|---------------------------------------------|
| Entities     | Singular noun            | `Invoice`, `Quote`, `Client`                |
| Repositories | Entity name + Repository | `InvoiceRepository`                         |
| Form Types   | Name + Type              | `InvoiceType`, `ClientType`                 |
| Listeners    | Purpose + Listener       | `InvoicePaidListener`                       |
| Actions      | Verb or purpose          | `CreateAction`, `EditAction`, `IndexAction` |
| Managers     | Entity + Manager         | `InvoiceManager`, `PaymentManager`          |
| Events       | Entity + Event           | `InvoiceEvent`, `PaymentEvent`              |

### File Organization

- **One class per file**
- **Filename matches class name**
- **Namespace reflects directory structure**
- **Use statement ordering:** const, class, function

### PHP Standards

- **Always use strict types:** `declare(strict_types=1);`
- **Type hints:** Always specify parameter and return types
- **Final classes:** Prefer `final` classes unless inheritance needed
- **Visibility:** Always specify (public, private, protected)
- **Constants:** Use class constants, not global constants

### Doctrine Best Practices

- **Use attributes** for mapping (PHP 8+)
- **Repository methods** should return typed arrays or collections
- **Query optimization:** Use joins to avoid N+1 queries
- **Use filters** for soft deletes (ArchivableFilter) and multi-tenancy (CompanyFilter)

### Symfony Best Practices

- **Constructor injection** for dependencies
- **Environment variables** for configuration
- **Twig** for all templates
- **Form types** for all forms
- **Validation** via annotations/attributes

---

## Database & ORM

### Configuration

**ORM:** Doctrine ORM 2.15+
**DBAL:** Doctrine DBAL 3.4+
**Mapping:** PHP 8 Attributes

### Database Support

- **MySQL** 5.7+, 8.0+ (recommended)
- **PostgreSQL** 10+
- **SQLite** (Recommended)

### UUID Primary Keys

All entities use UUID binary ordered time for primary keys:

```php
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Column(name: 'id', type: UlidType::NAME)]
#[ORM\Id]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UlidGenerator::class)]
private Ulid $id;
```

### Global Doctrine Filters

Two filters are always enabled:

1. **CompanyFilter** - Multi-tenancy: Automatically filters queries by company_id
2. **ArchivableFilter** - Soft deletes: Excludes archived records

### Migrations

Location: `/migrations/`

1. Migrations don't use raw SQL queries but instead use the Doctrine Schema tool. All newly created migrations should use the Schema tool to update the database schema.

2. Migrations don't use the standard naming convention from Doctrine. Instead, use a migration file per version. For example, for version 2.3.11, the migration file should be named `Version203011.php`.

3. From version 2.4 onwards, migrations are split into separate files using the following naming convention: `Version{major}{minor}{patch}_{partNumber}.php`. For example, for version 2.4.0, the migration files would be named `Version20400_1.php`, `Version20400_2.php`, etc.

**Commands:**

```bash
# Generate migration
bin/console doctrine:migrations:diff

# Run migrations
bin/console doctrine:migrations:migrate

# Check status
bin/console doctrine:migrations:status

# Rollback
bin/console doctrine:migrations:migrate prev
```

### Custom Doctrine Types

- `json_array` - Legacy JSON storage
- `uuid` - Standard UUID type
- Custom hydrators for Money objects

---

## API Development

### Framework

**API Platform 4.0+** with full REST API support

### Authentication

Token-based authentication via `X-API-TOKEN` header:

```bash
curl -H "X-API-TOKEN: your-token-here" https://example.com/api/invoices
```

### API Endpoints

Main resources:

- `/api/invoices` - Invoice CRUD
- `/api/quotes` - Quote CRUD
- `/api/clients` - Client CRUD with sub-resources
- `/api/contacts` - Contact management
- `/api/payments` - Payment records
- `/api/tax` - Tax rates

### Supported Formats

- JSON-LD (default)
- JSON-HAL
- JSON
- XML
- HTML (API documentation)

### Pagination

Default: 30 items per page

Query parameters:

- `page` - Page number
- `itemsPerPage` - Items per page (max 100)

Example:

```http request
GET /api/invoices?page=2&itemsPerPage=50
```

### Serialization

Custom normalizers for complex types:

- `MoneyNormalizer` - Money objects
- `CreditNormalizer` - Client credits
- `DiscountNormalizer` - Discount values
- `AdditionalContactDetailsNormalizer` - Contact details

Serialization groups:

- `invoice_api` - Invoice API representation
- `client_api` - Client API representation
- `Default` - Default serialization

### Creating API Resources

```php
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(
    collectionOperations: ['get', 'post'],
    itemOperations: ['get', 'put', 'delete'],
    normalizationContext: ['groups' => ['invoice_api']],
    denormalizationContext: ['groups' => ['invoice_write']]
)]
class Invoice
{
    // ...
}
```

---

## Frontend Development

### Build System

**Webpack 5** via Symfony Encore

Configuration: `webpack.config.js`

### JavaScript Architecture

**Framework:** Stimulus
**Pattern:** Stimulus controllers for modular JS

**Entry Points:**

- `assets/core.ts` - Global core functionality

### Asset Organization

```text
assets/
├── core.ts                 # Main entry point
└── scss/                   # Stylesheets
    ├── app.scss            # Main styles
    ├── email.scss          # Email templates
    └── pdf.scss            # PDF styles
```

### Styling

**Framework:** Tabler 1.4+ (Bootstrap 5.3)
**Preprocessor:** Sass
**Admin Template:** Tabler

### Build Commands

```bash
# Development
bun run dev        # Single build
bun run watch      # Watch mode
bun run dev-server # Dev server with HMR

# Production
bun run build # Optimized build

# Linting
bun run lint:js  # ESLint
bun run lint:css # StyleLint
```

### Adding New JavaScript

1. Create file in appropriate location:
    - Global code: `assets/core.ts`

2. Rebuild assets:
   ```bash
   bun run dev
   ```

---

## Security Considerations

### Authentication

- Username/password authentication
- API token authentication
- User invitation system

### Authorization

- Role-based access control (RBAC)
- Symfony Security component
- Voter pattern for complex permissions

### Multi-Tenancy

- Company-based data isolation
- Automatic filtering via CompanyFilter
- All company-aware entities use `CompanyAware` trait

### Payment Security

- PCI-compliant payment processing via Payum
- Secure token storage
- No raw card data storage

### API Security

- Token-based authentication required
- Token history tracking for audit
- Rate limiting (configure as needed)

### Sensitive Data

- Never commit credentials
- Use environment variables for secrets
- Encrypted values use defuse/php-encryption

### Best Practices

1. **Input Validation:** Always validate user input
2. **Output Escaping:** Twig auto-escapes, but be careful with `raw` filter
3. **SQL Injection:** Use Doctrine ORM/DBAL, never raw queries
4. **CSRF Protection:** Enabled by default on forms
5. **XSS Protection:** Validate and sanitize all user input

---

## Additional Resources

### Documentation

- [Symfony 7.1 Documentation](https://symfony.com/doc/7.1/index.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [API Platform Documentation](https://api-platform.com/docs/symfony/)
- [Payum Documentation](https://payum.gitbook.io/payum/)

### Important Files

- `CONTRIBUTING.md` - Contribution guidelines
- `README.md` - Project overview and installation
- `CHANGELOG.md` - Version history
- `SECURITY.md` - Security policy
- `UPGRADE.md` - Upgrade instructions

### Project Links

- [**Homepage:**](https://solidinvoice.co)
- [**Repository:**](https://github.com/SolidInvoice/SolidInvoice)
- [**Issues:**](https://github.com/SolidInvoice/SolidInvoice/issues)
- [**Docker Hub:**](https://hub.docker.com/r/solidinvoice/solidinvoice/)

---

## Quick Reference for AI Assistants

### When Adding Features

1. ✅ Identify the appropriate bundle (or create new if necessary)
2. ✅ Follow the bundle internal structure (Action, Entity, Repository, etc.)
3. ✅ Add proper type hints and strict types
4. ✅ Include file header comment
5. ✅ Write unit tests
6. ✅ Update API resources if needed
7. ✅ Run code quality tools (ECS, PHPStan)
8. ✅ Generate and run migrations if database changes

### When Fixing Bugs

1. ✅ Add a failing test that reproduces the bug
2. ✅ Fix the bug
3. ✅ Ensure the test passes
4. ✅ Run full test suite
5. ✅ Run static analysis

### When Refactoring

1. ✅ Ensure tests exist and pass
2. ✅ Consider using Rector for automated refactoring
3. ✅ Maintain backward compatibility unless major version
4. ✅ Update documentation if API changes
5. ✅ Verify all tests still pass

### Code Review Checklist

- [ ] Strict types declared
- [ ] Proper type hints (parameters and return types)
- [ ] File header present
- [ ] Follows PSR-12 and project standards
- [ ] Tests included
- [ ] No hardcoded values (use configuration)
- [ ] Proper error handling
- [ ] No security vulnerabilities
- [ ] Documentation updated if needed
- [ ] Passes all CI checks

---

## Design System Guide

This section provides comprehensive guidelines for implementing consistent, modern UI across all SolidInvoice pages. **All UI changes MUST follow these guidelines.**

### Design Philosophy

SolidInvoice follows a **Clean & Minimal** design aesthetic:

- **Content-focused**: Reduce visual noise, let data speak
- **Consistent spacing**: Use the 8px-based spacing scale
- **Subtle interactions**: Purposeful animations, no decorative effects
- **Accessible by default**: Proper contrast, focus states, ARIA attributes

### Design Tokens

All design values are defined as CSS custom properties with the `--swp-` prefix in `/assets/scss/design-system/_tokens.scss`.

#### Color Palette

| Token | Value | Usage |
|-------|-------|-------|
| `--swp-primary` | `#2e963a` | Main CTAs, links, active states |
| `--swp-primary-dark` | `#1f6c29` | Hover/active states |
| `--swp-primary-light` | `#e8f5e9` | Backgrounds, badges |
| `--swp-secondary` | `#f0a015` | Accent color (use sparingly) |
| `--swp-success` | `#10b981` | Success states, confirmations |
| `--swp-danger` | `#ef4444` | Errors, destructive actions |
| `--swp-warning` | `#f59e0b` | Warnings, cautions |
| `--swp-info` | `#3b82f6` | Informational content |

**Gray Scale (Slate-based):**
- `--swp-gray-50` to `--swp-gray-900` for text and backgrounds
- `--swp-text-primary`, `--swp-text-secondary`, `--swp-text-muted` for text hierarchy

#### Spacing Scale (8px base)

| Token | Value | Usage |
|-------|-------|-------|
| `--swp-space-1` | `0.25rem` (4px) | Tiny gaps |
| `--swp-space-2` | `0.5rem` (8px) | Icon gaps, tight spacing |
| `--swp-space-3` | `0.75rem` (12px) | Button padding |
| `--swp-space-4` | `1rem` (16px) | Standard form spacing |
| `--swp-space-6` | `1.5rem` (24px) | Card padding |
| `--swp-space-8` | `2rem` (32px) | Section spacing |

#### Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `--swp-radius-sm` | `6px` | Small elements, badges |
| `--swp-radius-md` | `8px` | Buttons, form controls |
| `--swp-radius-lg` | `12px` | Cards |
| `--swp-radius-xl` | `16px` | Modals |

#### Shadows

| Token | Usage |
|-------|-------|
| `--swp-shadow-sm` | Cards, subtle elevation |
| `--swp-shadow-md` | Hover states, dropdowns |
| `--swp-shadow-lg` | Modals, popovers |
| `--swp-shadow-primary` | Primary button glow |

### Typography Guidelines

| Element | Size Token | Weight | Color |
|---------|------------|--------|-------|
| Page title | `--swp-text-2xl` | `--swp-font-semibold` | `--swp-text-primary` |
| Card title | `--swp-text-lg` | `--swp-font-semibold` | `--swp-text-primary` |
| Section title | `--swp-text-md` | `--swp-font-semibold` | `--swp-text-primary` |
| Body text | `--swp-text-base` | `--swp-font-normal` | `--swp-body-color` |
| Help text | `--swp-text-sm` | `--swp-font-normal` | `--swp-text-muted` |
| Labels | `--swp-text-sm` | `--swp-font-medium` | `--swp-text-secondary` |
| Table headers | `--swp-text-xs` | `--swp-font-semibold` | `--swp-text-muted` |

---

### Platform UI Components (REQUIRED)

**Always use SolidWorx/Platform UI components when available.** These are Twig components that provide consistent, accessible UI elements.

**Available Components:**

| Component | Location | Docs |
|-----------|----------|------|
| `<twig:Ui:Alert>` | `vendor/solidworx/platform/src/Bundle/Ui/templates/components/Alert.html.twig` | `vendor/solidworx/platform/src/Bundle/Ui/Docs/Alert.md` |
| `<twig:Ui:Card>` | `vendor/solidworx/platform/src/Bundle/Ui/templates/components/Card.html.twig` | `vendor/solidworx/platform/src/Bundle/Ui/Docs/Card.md` |
| `<twig:Ui:Modal>` | `vendor/solidworx/platform/src/Bundle/Ui/templates/components/Modal.html.twig` | `vendor/solidworx/platform/src/Bundle/Ui/Docs/Modal.md` |

**Usage Examples:**

```twig
{# Card Component #}
<twig:Ui:Card title="Invoice Details" subtitle="View and edit">
    <p>Card body content here</p>

    <twig:block name="footer">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">{{ ux_icon('tabler:device-floppy') }} Save</button>
    </twig:block>
</twig:Ui:Card>

{# Alert Component #}
<twig:Ui:Alert type="success" icon="tabler:check" title="Saved!" :dismissible="true">
    Your changes have been saved successfully.
</twig:Ui:Alert>

{# Modal Component #}
<twig:Ui:Modal id="confirm-modal" title="Confirm Action" status="danger">
    <p>Are you sure you want to proceed?</p>

    <twig:block name="footer">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger">Confirm</button>
    </twig:block>
</twig:Ui:Modal>
```

**When a style/feature is needed but not supported:**
1. First, create custom HTML to achieve the desired result
2. Note what feature is missing for future addition to SolidWorx/Platform/UiBundle
3. New components will be added to Platform over time

**Do NOT:**
- Create custom implementations when a Platform component exists
- Override Platform component styles with inline styles
- Duplicate Platform component functionality

---

### Component Usage

#### Buttons

**Variants and when to use:**

| Class | Usage |
|-------|-------|
| `btn-primary` | Main page action, form submit (Save, Create, Confirm) |
| `btn-secondary` | Secondary accent actions (rarely used) |
| `btn-outline-primary` | Secondary actions, Cancel buttons |
| `btn-outline-secondary` | Tertiary actions |
| `btn-ghost` | Minimal actions, navigation-like buttons |
| `btn-danger` | Destructive actions ONLY (Delete, Remove) |
| `btn-outline-danger` | Less prominent destructive actions |

**Icon usage in buttons:**
```html
<button class="btn btn-primary">
    {{ ux_icon('tabler:device-floppy') }} Save
</button>
```

- Icon BEFORE text
- Use `gap-2` (handled by `.btn` class)
- Icon size: 18px default, 16px for small, 20px for large

**Button states:**
- Hover: Subtle lift (`translateY(-1px)`) + shadow increase
- Active: Return to baseline + reduced shadow
- Disabled: 50% opacity, no pointer events
- Loading: Use `.btn-loading` class for spinner

#### Cards

**Standard card structure:**
```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
        <div class="card-header-actions">
            <!-- Optional action buttons -->
        </div>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
    <div class="card-footer">
        <!-- Action buttons -->
    </div>
</div>
```

**Card variants:**
- Default: White background, subtle shadow, 12px radius
- `.card-flat`: No shadow, light border (for nested cards)
- `.card-interactive`: Hover lift effect (for clickable cards)
- `.card-muted`: Gray background (for secondary content)
- `.card-status-*`: Colored top border indicator

**Card sections (within body):**
```html
<div class="card-section">
    <div class="card-section-header">
        <h4 class="card-section-title">
            {{ ux_icon('tabler:icon') }} Section Title
        </h4>
    </div>
    <!-- Section content -->
</div>
```

#### Forms

**Standard form field structure:**
```html
<div class="mb-4">
    <label class="form-label required">Field Label</label>
    <input type="text" class="form-control" />
    <div class="form-help">Help text appears here</div>
</div>
```

**Form layouts:**
- Single column: Default for settings, simple forms
- Two columns: Use `.row` with `.col-md-6` for related fields
- Use `.form-row-2` or `.form-row-3` for grid layouts

**Toggle switches:**
```html
<div class="form-switch-field">
    <div class="form-check form-switch form-switch-wrapper">
        <input type="checkbox" class="form-check-input" role="switch" />
        <label class="form-check-label">Toggle Label</label>
    </div>
    <div class="form-help">Help text</div>
</div>
```

**Input with icon:**
```html
<div class="input-group input-icon-group">
    <span class="input-group-text">
        {{ ux_icon('tabler:mail', {width: 18, height: 18}) }}
    </span>
    <input type="email" class="form-control" />
</div>
```

---

### Form Actions Pattern (CRITICAL)

**Every form MUST follow this pattern:**

```
┌─────────────────────────────────────────────────────────────────┐
│ [Cancel (btn-ghost)]                     [Save (btn-primary + icon)]│
└─────────────────────────────────────────────────────────────────┘
```

**Standard implementation:**
```html
<div class="card-footer">
    <div class="form-actions">
        <button type="button" class="btn btn-ghost">
            {{ 'Cancel'|trans }}
        </button>
        <button type="submit" class="btn btn-primary">
            {{ ux_icon('tabler:device-floppy') }} {{ 'Save'|trans }}
        </button>
    </div>
</div>
```

**Rules:**
1. Cancel button: Ghost or outline style, positioned LEFT or BEFORE save
2. Save button: Primary style, with floppy disk icon, positioned RIGHT
3. Use `.form-actions-spread` to push Cancel to far left
4. Danger actions (Delete): Use separate confirmation flow, NOT in main form actions

**Multi-action forms:**
```html
<div class="form-actions form-actions-spread">
    <button type="button" class="btn btn-ghost">Cancel</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary">Save as Draft</button>
        <button type="submit" class="btn btn-primary">
            {{ ux_icon('tabler:device-floppy') }} Save
        </button>
    </div>
</div>
```

---

### Page Patterns

#### Settings Pages

**Rules:**
- Max-width: 900px, centered (use `.settings-page`)
- Use horizontal tabs for **2-7 sections**
- Use sidebar navigation for **8+ sections**
- Each settings group in its own card
- Section headers with icon + title
- Danger zone at the bottom with red border

**Tab navigation:**
```html
<nav class="settings-tabs">
    <a href="#company" class="settings-tab active">
        {{ ux_icon('tabler:building') }} Company
    </a>
    <a href="#invoices" class="settings-tab">
        {{ ux_icon('tabler:file-invoice') }} Invoices
    </a>
</nav>
```

**Danger zone:**
```html
<div class="settings-danger-zone">
    <div class="card-header">
        <div class="settings-card-icon">
            {{ ux_icon('tabler:alert-triangle') }}
        </div>
        <div>
            <h3 class="settings-card-title">Danger Zone</h3>
            <p class="settings-card-description">Irreversible actions</p>
        </div>
    </div>
    <div class="card-body">
        <!-- Danger actions with confirmation -->
    </div>
</div>
```

#### List Pages (DataGrid)

**Structure:**
```html
<div class="list-page">
    <!-- 1. Page header with title + Create button -->
    <div class="list-header">
        <h1 class="list-title">Invoices</h1>
        <div class="list-actions">
            <a href="#" class="btn btn-primary">
                {{ ux_icon('tabler:plus') }} Create Invoice
            </a>
        </div>
    </div>

    <!-- 2. Search/Filter bar (optional) -->
    <div class="list-filters">
        <div class="list-search">...</div>
        <div class="list-filter-group">...</div>
    </div>

    <!-- 3. Data table -->
    <div class="list-content">
        <table class="table">...</table>
    </div>

    <!-- 4. Pagination -->
    <div class="list-footer">
        <span class="list-footer-info">Showing 1-10 of 50</span>
        <nav class="pagination">...</nav>
    </div>
</div>
```

**Empty state:**
```html
<div class="list-empty">
    <div class="list-empty-icon">
        {{ ux_icon('tabler:file-invoice') }}
    </div>
    <h3 class="list-empty-title">No invoices yet</h3>
    <p class="list-empty-description">Create your first invoice to get started.</p>
    <a href="#" class="btn btn-primary">
        {{ ux_icon('tabler:plus') }} Create Invoice
    </a>
</div>
```

#### Form Pages (Create/Edit)

**Structure:**
```html
<div class="form-page">
    <form>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Invoice</h3>
            </div>
            <div class="card-body">
                <!-- Form fields -->
            </div>
            <div class="card-footer">
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        {{ ux_icon('tabler:device-floppy') }} Save
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
```

---

### Modals

**Standard modal:**
```html
<div class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</div>
```

**Sizes:** `.modal-sm` (400px), default (500px), `.modal-lg` (800px), `.modal-xl` (1140px)

**Danger/Delete confirmation:**
```html
<div class="modal fade modal-danger">
    <div class="modal-dialog modal-confirm">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-confirm-icon icon-danger">
                    {{ ux_icon('tabler:trash') }}
                </div>
                <h3 class="modal-confirm-title">Delete Invoice?</h3>
                <p class="modal-confirm-message">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost">Cancel</button>
                <button class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>
```

---

### Alerts

**Types and usage:**
- `alert-success`: Confirmation of completed action
- `alert-danger`: Error messages, failed operations
- `alert-warning`: Cautions, important notices
- `alert-info`: Helpful tips, neutral information
- `alert-primary`: Brand-related notifications

**Structure:**
```html
<div class="alert alert-success" role="alert">
    {{ ux_icon('tabler:check') }}
    <div class="alert-content">
        <div class="alert-title">Success!</div>
        <div class="alert-message">Invoice has been saved.</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
```

---

### Tables/DataGrids

**Table styling:**
- Headers: Uppercase, small text (`--swp-text-xs`), muted color
- Rows: Adequate padding (`1rem 1.5rem`), subtle hover
- Borders: Light gray (`--swp-border-light`), minimal
- No alternating row colors (hover is enough)

**Column types:**
- `.table-actions`: Right-aligned, action buttons
- `.table-checkbox`: Checkbox column (narrow)
- `.table-date`: Date column (no wrap)
- `.table-amount`: Right-aligned, monospace
- `.table-id`: Monospace, muted

---

### Animation Guidelines

**Transitions:**
- Hover effects: `150-200ms`
- State changes: `200-300ms`
- Modal open/close: `200ms`

**What to animate:**
- Hover: Shadow increase, subtle lift (`translateY(-1px)`)
- Focus: Visible focus ring with primary color
- Button press: Return to baseline

**What NOT to animate:**
- Text content changes
- Active states (should be instant)
- Background images

---

### Accessibility Checklist

- [ ] All interactive elements have visible focus states
- [ ] Color contrast ratio: 4.5:1 for text, 3:1 for UI
- [ ] Form fields have associated labels
- [ ] Required fields marked with `aria-required="true"`
- [ ] Error messages linked with `aria-describedby`
- [ ] Icon-only buttons have `aria-label`
- [ ] Modals trap focus and close on Escape
- [ ] Loading states announced to screen readers

---

### File Reference

**Design System (SolidInvoice custom styles):**

| File | Purpose |
|------|---------|
| `/assets/scss/design-system/_tokens.scss` | Design tokens (colors, spacing, shadows, transitions) |
| `/assets/scss/design-system/_typography.scss` | Typography scale and utility classes |
| `/assets/scss/_forms.scss` | Form field enhancements |
| `/assets/scss/settings.scss` | Settings page specific styles |
| `/src/CoreBundle/Resources/views/Form/fields.html.twig` | Form field templates |

**Platform UI Components:**

| File | Purpose |
|------|---------|
| `vendor/solidworx/platform/src/Bundle/Ui/Docs/Card.md` | Card component documentation |
| `vendor/solidworx/platform/src/Bundle/Ui/Docs/Alert.md` | Alert component documentation |
| `vendor/solidworx/platform/src/Bundle/Ui/Docs/Modal.md` | Modal component documentation |

**Note:** Button, table, modal, and alert styling comes from **Tabler/Bootstrap**. Do not create custom component SCSS files that duplicate framework styles.

---

### Quick Reference Checklist

When implementing any UI:

1. ✅ **Use Platform UI components** (`<twig:Ui:Card>`, `<twig:Ui:Alert>`, `<twig:Ui:Modal>`) when available
2. ✅ Use design tokens for all colors, spacing, shadows
3. ✅ Follow form actions pattern (Cancel left/ghost, Save right/primary with icon)
4. ✅ Use appropriate button variant for the action type
5. ✅ Settings pages use tabs for < 8 sections
6. ✅ Tables use Tabler/Bootstrap default styling
7. ✅ Accessibility: focus states, ARIA labels, proper contrast
8. ✅ Transitions are subtle and functional (150-300ms)
9. ✅ No hardcoded color/spacing values - use CSS variables
10. ✅ **Do NOT duplicate Bootstrap/Tabler styles** - use framework defaults

---

<frontend_aesthetics>
You tend to converge toward generic, "on distribution" outputs. In frontend design, this creates what users call the "AI slop" aesthetic. Avoid this: make creative, distinctive frontends that surprise and delight. Focus on:

Typography: Choose fonts that are beautiful, unique, and interesting. Avoid generic fonts like Arial and Inter; opt instead for distinctive choices that elevate the frontend's aesthetics.

Color & Theme: Commit to a cohesive aesthetic. Use CSS variables for consistency. Dominant colors with sharp accents outperform timid, evenly-distributed palettes. Draw from IDE themes and cultural aesthetics for inspiration.

Motion: Use animations for effects and micro-interactions. Prioritize CSS-only solutions for HTML. Use Motion library for React when available. Focus on high-impact moments: one well-orchestrated page load with staggered reveals (animation-delay) creates more delight than scattered micro-interactions.

Backgrounds: Create atmosphere and depth rather than defaulting to solid colors. Layer CSS gradients, use geometric patterns, or add contextual effects that match the overall aesthetic.

Avoid generic AI-generated aesthetics:
- Overused font families (Inter, Roboto, Arial, system fonts)
- Clichéd color schemes (particularly purple gradients on white backgrounds)
- Predictable layouts and component patterns
- Cookie-cutter design that lacks context-specific character

Interpret creatively and make unexpected choices that feel genuinely designed for the context. Vary between light and dark themes, different fonts, different aesthetics. You still tend to converge on common choices (Space Grotesk, for example) across generations. Avoid this: it is critical that you think outside the box!
</frontend_aesthetics>

---

**Last Updated:** 2025-12-31
**Document Version:** 1.1.0
**SolidInvoice Version:** 3.0.0-dev
