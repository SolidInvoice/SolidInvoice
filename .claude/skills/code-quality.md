# Code Quality & Standards

## Required File Header

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

## ECS (Easy Coding Standard)

Config: `ecs.php`

Standards: PSR-12, Symfony, PHPUnit, clean code principles

```bash
bin/ecs check           # Check
bin/ecs check --fix     # Fix
```

## PHPStan (Static Analysis)

Config: `phpstan.neon`
Level: 6
Baseline: `phpstan-baseline.neon`

```bash
bin/phpstan analyse              # With baseline
bin/phpstan analyse --no-baseline # Without baseline
```

## Rector (Refactoring)

Config: `rector.php`

Rules: PHP upgrades, Symfony best practices, Doctrine improvements, PHPUnit modernization

```bash
bin/rector process --dry-run  # Preview
bin/rector process            # Apply
```

## Pre-commit Checklist

1. `bin/ecs check --fix`
2. `bin/phpstan analyse`
3. `bin/phpunit`
4. File header present
5. `declare(strict_types=1);`

## PHP Standards

- Always use strict types
- Always specify parameter and return types
- Prefer `final` classes
- Always specify visibility
- **Use PHP 8.1+ backed enums for fixed sets of values (status, type, etc.), NEVER class constants**
- Use class constants for configuration values, not for enum-like values

## CI/CD Checks

Every PR triggers:
- Unit tests (PHP 8.4/8.5, MySQL 8.0)
- ECS + Super-Linter
- PHPStan + Qodana
- Security checks (Composer, CodeQL)
