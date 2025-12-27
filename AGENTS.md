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

---

## Technology Stack

### Backend

| Component          | Technology              | Version                |
|--------------------|-------------------------|------------------------|
| Framework          | Symfony                 | 7.1+                   |
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
| UI Framework         | Bootstrap                | 4.6+    |
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

- **Docker:** Official SolidInvoice Docker images available on Docker Hub (solidinvoice/solidinvoice). Runs the Frankenphp binary inside the container.
- **Frankenphp:** Official recommended installation method. Application is built into a single binary, which contains PHP, all required extension, the web server, and the application code.
- **Archive:** ZIP/TAR archives for manual installation (all assets pre-compiled)

---

## Codebase Structure

### Root Directory Layout

```
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
├── frankenphp/             # Frankenphp code and config (Go files and shell scripts to build and compile the binary)
├── migrations/             # Database migrations
├── public/                 # Web-accessible files
├── scripts/                # Shell scripts for various tasks
├── src/                    # Application source code (19 bundles)
├── templates/              # Twig templates which overrides external bundle templates
├── tests/                  # Test bootstrap and utilities
├── var/                    # Runtime data (cache, logs)
├── vendor/                 # Composer dependencies
```

### Bundle Organization (src/)

SolidInvoice uses a modular bundle architecture with 19 bundles:

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
- **MenuBundle** - Menu building with KnpMenuBundle
- **DataGridBundle** - Data grid/table rendering
- **FormBundle** - Form customizations

#### Specialized Services

- **NotificationBundle** - Multi-channel notifications (email, SMS, webhooks)
- **MoneyBundle** - Money type handling with moneyphp/money library
- **SaasBundle** - SaaS features like subscriptions and usage tracking

### Bundle Internal Structure

Each bundle follows this pattern:

```
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

```
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

1. Migrations doesn't use raw SQL queries but instead uses the Doctrine Schema tool. All newly created migrations should use the Schema tool to update the database schema.

2. Migrations doesn't use the standard naming convention from Doctrine. Instead, use a migration file per version. For example, for version 2.3.11, the migration file should be named `Version203011.php`.

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

```
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

```
assets/
├── core.ts              # Main entry point
└── scss/
    ├── app.scss             # Main styles
    ├── email.scss           # Email templates
    └── pdf.scss             # PDF styles
```

### Styling

**Framework:** Bootstrap 4.6.2
**Preprocessor:** Sass
**Admin Template:** AdminLTE

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

- **Homepage:** https://solidinvoice.co
- **Repository:** https://github.com/SolidInvoice/SolidInvoice
- **Issues:** https://github.com/SolidInvoice/SolidInvoice/issues
- **Docker Hub:** https://hub.docker.com/r/solidinvoice/solidinvoice/

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

**Last Updated:** 2025-12-27
**Document Version:** 1.0.0
**SolidInvoice Version:** 2.3.11
