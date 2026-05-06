# Feature Gate Infrastructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a generic, plan-aware feature gate that lets any SolidInvoice bundle ask "is feature X available?" / "can the current company use one more?" without importing SaaS classes — so self-hosted gets unlimited everything for free, while SaaS deployments get plan-driven answers.

**Architecture:** A `FeatureGate` interface lives in `PlatformBundle` (always loaded) with a no-op default implementation. `SaasBundle` provides a plan-driven implementation that overrides the default when loaded. `SolidInvoice` plugs in a `SubscriberResolver` that pulls the current company via `CompanySelector`. Twig functions and value objects move to `PlatformBundle` so they're always available.

**Tech Stack:** PHP 8.4, Symfony 7.2+, Twig, PHPUnit 12, Doctrine ORM. Affects two repos: `solidworx/platform` (vendor — `PlatformBundle` and `SaasBundle`) and `solidworx/solidinvoice` (this repo).

**Source spec:** `docs/superpowers/specs/2026-05-06-feature-gate-design.md`

**Working tree note:** `vendor/solidworx/platform/` is a symlink to a checked-out platform repo at `/Users/pierre/projects/SolidWorx/platform/` (currently on `main`). Vendor edits are saved in that repo's working tree. SolidInvoice's `.gitignore` excludes `/vendor/`, so:

- Tasks that touch only vendor files commit inside the platform repo: `cd vendor/solidworx/platform && git add … && git commit …`.
- Tasks that touch SolidInvoice files commit at the SolidInvoice top level.
- Task 3 touches both — it needs two commits, one in each repo.

`vendor/bin/phpunit`, `vendor/bin/phpstan`, etc. inside the symlink resolve to the platform repo's tools and work normally.

---

## Phase 1 — PlatformBundle: move shared classes & introduce contract

### Task 1: Move `FeatureType` enum to PlatformBundle

**Files:**
- Move: `vendor/solidworx/platform/src/Bundle/Saas/Enum/FeatureType.php` → `vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureType.php`
- Modify (use statements): `vendor/solidworx/platform/src/Bundle/Saas/Feature/FeatureValue.php`, `FeatureConfig.php`, `FeatureConfigRegistry.php`, `PlanFeatureManager.php`, `Entity/PlanFeature.php`

- [ ] **Step 1: Create the file at its new location with the new namespace**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureType.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

enum FeatureType: string
{
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case STRING = 'string';
    case ARRAY = 'array';
}
```

- [ ] **Step 2: Delete the old file**

```bash
rm vendor/solidworx/platform/src/Bundle/Saas/Enum/FeatureType.php
```

- [ ] **Step 3: Update every consumer's `use` statement**

Find consumers:

```bash
grep -rln 'SolidWorx\\Platform\\SaasBundle\\Enum\\FeatureType' vendor/solidworx/platform/src/
```

In each match, replace `use SolidWorx\Platform\SaasBundle\Enum\FeatureType;` with `use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;`. Expected files: `FeatureValue.php`, `FeatureConfig.php`, `FeatureConfigRegistry.php`, `PlanFeatureManager.php`, `Entity/PlanFeature.php`.

- [ ] **Step 4: Run platform tests to verify nothing broke**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit --filter Feature
```

Expected: all tests pass (the move is mechanical — no behavior change).

- [ ] **Step 5: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Move FeatureType enum from SaasBundle to PlatformBundle"
```

---

### Task 2: Move `FeatureValue` value object to PlatformBundle

**Files:**
- Move: `vendor/solidworx/platform/src/Bundle/Saas/Feature/FeatureValue.php` → `vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureValue.php`
- Move test: `vendor/solidworx/platform/tests/Bundle/Saas/Feature/FeatureValueTest.php` → `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/FeatureValueTest.php`
- Modify (use statements): `FeatureConfig.php`, `PlanFeatureManager.php`, `PlanFeatureToggle.php`, `Entity/PlanFeature.php`, `Twig/Runtime/FeatureRuntime.php`

- [ ] **Step 1: Create the file at its new location**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureValue.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

use function is_array;
use function is_bool;
use function is_int;

final readonly class FeatureValue
{
    public const int UNLIMITED = -1;

    /**
     * @param array<mixed> $value
     */
    public function __construct(
        public string $key,
        public FeatureType $type,
        public int|bool|string|array $value,
    ) {
    }

    public function isUnlimited(): bool
    {
        return $this->type === FeatureType::INTEGER && $this->value === self::UNLIMITED;
    }

    public function isEnabled(): bool
    {
        return match ($this->type) {
            FeatureType::BOOLEAN => $this->value === true,
            FeatureType::INTEGER => $this->value !== 0,
            FeatureType::STRING => $this->value !== '',
            FeatureType::ARRAY => $this->value !== [],
        };
    }

    public function asInt(): int
    {
        if (is_int($this->value)) {
            return $this->value;
        }

        if (is_bool($this->value)) {
            return $this->value ? 1 : 0;
        }

        return (int) $this->value;
    }

    public function asBool(): bool
    {
        if (is_bool($this->value)) {
            return $this->value;
        }

        if (is_int($this->value)) {
            return $this->value !== 0;
        }

        return (bool) $this->value;
    }

    public function asString(): string
    {
        if (is_array($this->value)) {
            return implode(',', $this->value);
        }

        if (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        }

        return (string) $this->value;
    }

    /**
     * @return array<mixed>
     */
    public function asArray(): array
    {
        if (is_array($this->value)) {
            return $this->value;
        }

        return [$this->value];
    }

    public function allows(int $currentUsage): bool
    {
        if ($this->isUnlimited()) {
            return true;
        }

        if ($this->type !== FeatureType::INTEGER) {
            return $this->isEnabled();
        }

        return $currentUsage < $this->asInt();
    }

    public function getRemainingQuota(int $currentUsage): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        if ($this->type !== FeatureType::INTEGER) {
            return null;
        }

        return max(0, $this->asInt() - $currentUsage);
    }
}
```

- [ ] **Step 2: Delete the old file**

```bash
rm vendor/solidworx/platform/src/Bundle/Saas/Feature/FeatureValue.php
```

- [ ] **Step 3: Move and re-namespace the test**

```bash
mkdir -p vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature
git mv vendor/solidworx/platform/tests/Bundle/Saas/Feature/FeatureValueTest.php \
       vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/FeatureValueTest.php 2>/dev/null \
  || mv vendor/solidworx/platform/tests/Bundle/Saas/Feature/FeatureValueTest.php \
        vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/FeatureValueTest.php
```

Edit the moved test file: change `namespace SolidWorx\Platform\Tests\Bundle\Saas\Feature;` → `namespace SolidWorx\Platform\Tests\Bundle\PlatformBundle\Feature;`, and replace any `use SolidWorx\Platform\SaasBundle\Feature\FeatureValue;` / `use SolidWorx\Platform\SaasBundle\Enum\FeatureType;` with the PlatformBundle paths.

- [ ] **Step 4: Update `use` statements in remaining consumers**

```bash
grep -rln 'SolidWorx\\Platform\\SaasBundle\\Feature\\FeatureValue' vendor/solidworx/platform/src/
```

In each match (`FeatureConfig.php`, `PlanFeatureManager.php`, `PlanFeatureToggle.php`, `Entity/PlanFeature.php`, `Twig/Runtime/FeatureRuntime.php`), replace `use SolidWorx\Platform\SaasBundle\Feature\FeatureValue;` with `use SolidWorx\Platform\PlatformBundle\Feature\FeatureValue;`.

- [ ] **Step 5: Run the moved test**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/FeatureValueTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Run full SaaS feature test suite to confirm consumers still work**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit --filter Feature
```

Expected: all green.

- [ ] **Step 7: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Move FeatureValue from SaasBundle to PlatformBundle"
```

---

### Task 3: Move `SubscribableInterface` to PlatformBundle

**Files:**
- Move: `vendor/solidworx/platform/src/Bundle/Saas/Subscriber/SubscribableInterface.php` → `vendor/solidworx/platform/src/Bundle/Platform/Feature/SubscribableInterface.php`
- Modify (use statements): every file in `vendor/solidworx/platform/src/Bundle/Saas/` that imports it (see grep below), plus `src/CoreBundle/Entity/Company.php` in SolidInvoice.

- [ ] **Step 1: Create the file at its new location**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/SubscribableInterface.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

interface SubscribableInterface
{
}
```

- [ ] **Step 2: Delete the old file**

```bash
rm vendor/solidworx/platform/src/Bundle/Saas/Subscriber/SubscribableInterface.php
rmdir vendor/solidworx/platform/src/Bundle/Saas/Subscriber 2>/dev/null || true
```

- [ ] **Step 3: Update vendor consumers**

```bash
grep -rln 'SolidWorx\\Platform\\SaasBundle\\Subscriber\\SubscribableInterface' vendor/solidworx/platform/
```

In every match, replace `use SolidWorx\Platform\SaasBundle\Subscriber\SubscribableInterface;` with `use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;`. Expected files include:

- `src/Bundle/Saas/Config/SaasConfiguration.php`
- `src/Bundle/Saas/Security/Voter/PlanFeatureVoter.php`
- `src/Bundle/Saas/Entity/Subscription.php`
- `src/Bundle/Saas/Subscription/SubscriptionProviderInterface.php`
- `src/Bundle/Saas/Subscription/SubscriptionManager.php`
- `src/Bundle/Saas/Feature/PlanFeatureToggle.php`
- `src/Bundle/Saas/Feature/PlanFeatureManager.php`
- `src/Bundle/Saas/Feature/FeatureToggleInterface.php`
- `src/Bundle/Saas/Twig/Runtime/FeatureRuntime.php`
- `src/Bundle/Saas/DependencyInjection/CompilerPass/ResolveTargetEntityPass.php`
- `src/Bundle/Saas/DependencyInjection/SolidWorxPlatformSaasExtension.php`
- `src/Bundle/Saas/Console/Command/SubscriptionListCommand.php`

- [ ] **Step 4: Update SolidInvoice consumers**

```bash
grep -rln 'SolidWorx\\Platform\\SaasBundle\\Subscriber\\SubscribableInterface' src/
```

Currently `src/CoreBundle/Entity/Company.php` references it. Update its `use` statement to the new namespace.

- [ ] **Step 5: Run vendor test suite**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit
```

Expected: all green.

- [ ] **Step 6: Run SolidInvoice static analysis to confirm Company entity still type-checks**

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice && bin/phpstan analyse src/CoreBundle/Entity/Company.php
```

Expected: no errors.

- [ ] **Step 7: Commit — two commits, one per repo**

Vendor changes (the move + vendor consumer updates):

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Move SubscribableInterface from SaasBundle to PlatformBundle"
```

SolidInvoice change (`Company` entity import):

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice
git add src/CoreBundle/Entity/Company.php
git commit -m "Update Company entity for relocated SubscribableInterface"
```

---

### Task 4: Add `UpgradeOptions` and `PlanReference` DTOs

**Files:**
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/PlanReference.php`
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/UpgradeOptions.php`
- Create: `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/UpgradeOptionsTest.php`

- [ ] **Step 1: Write the failing test for `UpgradeOptions`**

```php
// vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/UpgradeOptionsTest.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\Tests\Bundle\PlatformBundle\Feature;

use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\PlanReference;
use SolidWorx\Platform\PlatformBundle\Feature\UpgradeOptions;

final class UpgradeOptionsTest extends TestCase
{
    public function testIsEmptyWithNoPlans(): void
    {
        $options = new UpgradeOptions([]);

        self::assertTrue($options->isEmpty());
        self::assertSame([], $options->plans);
    }

    public function testIsNotEmptyWithPlans(): void
    {
        $plan = new PlanReference('01HX', 'Pro');
        $options = new UpgradeOptions([$plan]);

        self::assertFalse($options->isEmpty());
        self::assertCount(1, $options->plans);
        self::assertSame('01HX', $options->plans[0]->id);
        self::assertSame('Pro', $options->plans[0]->name);
    }
}
```

- [ ] **Step 2: Run the test, expect failure**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/UpgradeOptionsTest.php
```

Expected: FAIL with "Class \"SolidWorx\\Platform\\PlatformBundle\\Feature\\PlanReference\" not found".

- [ ] **Step 3: Implement `PlanReference`**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/PlanReference.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

final readonly class PlanReference
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}
```

- [ ] **Step 4: Implement `UpgradeOptions`**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/UpgradeOptions.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

final readonly class UpgradeOptions
{
    /**
     * @param list<PlanReference> $plans
     */
    public function __construct(
        public array $plans,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->plans === [];
    }
}
```

- [ ] **Step 5: Run the test, expect pass**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/UpgradeOptionsTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 6: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Add UpgradeOptions and PlanReference DTOs to PlatformBundle"
```

---

### Task 5: Add `SubscriberResolver` interface and `NullSubscriberResolver`

**Files:**
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/SubscriberResolver.php`
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/NullSubscriberResolver.php`
- Create: `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/NullSubscriberResolverTest.php`

- [ ] **Step 1: Write the failing test**

```php
// vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/NullSubscriberResolverTest.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\Tests\Bundle\PlatformBundle\Feature;

use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\NullSubscriberResolver;

final class NullSubscriberResolverTest extends TestCase
{
    public function testResolveAlwaysReturnsNull(): void
    {
        $resolver = new NullSubscriberResolver();

        self::assertNull($resolver->resolve());
    }
}
```

- [ ] **Step 2: Run the test, expect failure**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/NullSubscriberResolverTest.php
```

Expected: FAIL with "Class \"SolidWorx\\Platform\\PlatformBundle\\Feature\\NullSubscriberResolver\" not found".

- [ ] **Step 3: Create the interface**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/SubscriberResolver.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

interface SubscriberResolver
{
    /**
     * Returns the current subject for an implicit gate check, or null if none.
     */
    public function resolve(): ?SubscribableInterface;
}
```

- [ ] **Step 4: Create the null implementation**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/NullSubscriberResolver.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

use Override;

final readonly class NullSubscriberResolver implements SubscriberResolver
{
    #[Override]
    public function resolve(): ?SubscribableInterface
    {
        return null;
    }
}
```

- [ ] **Step 5: Run the test, expect pass**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/NullSubscriberResolverTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Add SubscriberResolver interface and NullSubscriberResolver"
```

---

### Task 6: Add `FeatureGate` interface and `NoopFeatureGate`

**Files:**
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureGate.php`
- Create: `vendor/solidworx/platform/src/Bundle/Platform/Feature/NoopFeatureGate.php`
- Create: `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/NoopFeatureGateTest.php`

- [ ] **Step 1: Write the failing test**

```php
// vendor/solidworx/platform/tests/Bundle/PlatformBundle/Feature/NoopFeatureGateTest.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\Tests\Bundle\PlatformBundle\Feature;

use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureValue;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;

final class NoopFeatureGateTest extends TestCase
{
    public function testResolveReturnsUnlimitedFeatureValue(): void
    {
        $gate = new NoopFeatureGate();

        $value = $gate->resolve('any_key');

        self::assertSame('any_key', $value->key);
        self::assertSame(FeatureType::INTEGER, $value->type);
        self::assertSame(FeatureValue::UNLIMITED, $value->value);
        self::assertTrue($value->isUnlimited());
    }

    public function testIsEnabledAlwaysTrue(): void
    {
        $gate = new NoopFeatureGate();

        self::assertTrue($gate->isEnabled('any_key'));
        self::assertTrue($gate->isEnabled('any_key', $this->subscriber()));
    }

    public function testCanUseAlwaysTrueRegardlessOfUsage(): void
    {
        $gate = new NoopFeatureGate();

        self::assertTrue($gate->canUse('any_key'));
        self::assertTrue($gate->canUse('any_key', 999_999));
        self::assertTrue($gate->canUse('any_key', 999_999, $this->subscriber()));
    }

    public function testRemainingAlwaysNullForUnlimited(): void
    {
        $gate = new NoopFeatureGate();

        self::assertNull($gate->remaining('any_key'));
        self::assertNull($gate->remaining('any_key', 100));
    }

    public function testUpgradeOptionsAlwaysEmpty(): void
    {
        $gate = new NoopFeatureGate();

        $options = $gate->upgradeOptions('any_key');

        self::assertTrue($options->isEmpty());
    }

    private function subscriber(): SubscribableInterface
    {
        return new class implements SubscribableInterface {};
    }
}
```

- [ ] **Step 2: Run the test, expect failure**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/NoopFeatureGateTest.php
```

Expected: FAIL with "Class \"SolidWorx\\Platform\\PlatformBundle\\Feature\\NoopFeatureGate\" not found".

- [ ] **Step 3: Create the `FeatureGate` interface**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/FeatureGate.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

interface FeatureGate
{
    /**
     * Resolve the full feature value (enabled state, limit, type, raw value).
     *
     * When $for is null, implementations should resolve the current subject
     * via the injected SubscriberResolver. Implementations are free to fall
     * back to configured defaults when no subject is available.
     */
    public function resolve(string $featureKey, ?SubscribableInterface $for = null): FeatureValue;

    /**
     * Convenience: is the feature enabled for this subject?
     */
    public function isEnabled(string $featureKey, ?SubscribableInterface $for = null): bool;

    /**
     * Convenience: can the subject use one more, given current usage?
     */
    public function canUse(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): bool;

    /**
     * Convenience: how many remaining?
     *
     * Returns null when the feature is unlimited or non-quantitative.
     */
    public function remaining(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int;

    /**
     * Returns upgrade guidance when a feature is unavailable.
     *
     * Empty in non-SaaS deployments; populated when the SaaS implementation
     * is wired and other plans expose the feature.
     */
    public function upgradeOptions(string $featureKey): UpgradeOptions;
}
```

- [ ] **Step 4: Implement `NoopFeatureGate`**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Feature/NoopFeatureGate.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Feature;

use Override;

/**
 * Default FeatureGate used in non-SaaS deployments.
 *
 * Reports every feature as available with no quantitative limit. Never consults
 * a registry — self-hosted has no opinion about which feature keys exist.
 */
final readonly class NoopFeatureGate implements FeatureGate
{
    #[Override]
    public function resolve(string $featureKey, ?SubscribableInterface $for = null): FeatureValue
    {
        return new FeatureValue($featureKey, FeatureType::INTEGER, FeatureValue::UNLIMITED);
    }

    #[Override]
    public function isEnabled(string $featureKey, ?SubscribableInterface $for = null): bool
    {
        return true;
    }

    #[Override]
    public function canUse(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): bool
    {
        return true;
    }

    #[Override]
    public function remaining(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int
    {
        return null;
    }

    #[Override]
    public function upgradeOptions(string $featureKey): UpgradeOptions
    {
        return new UpgradeOptions([]);
    }
}
```

- [ ] **Step 5: Run the test, expect pass**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Feature/NoopFeatureGateTest.php
```

Expected: PASS (5 tests).

- [ ] **Step 6: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Add FeatureGate interface and NoopFeatureGate default"
```

---

### Task 7: Wire PlatformBundle defaults via service config

**Files:**
- Modify: `vendor/solidworx/platform/src/Bundle/Platform/Resources/config/services.php`

The `_defaults` block already has `autowire` + `autoconfigure`, and the `load()` directive picks up everything in `src/Bundle/Platform/` — so `NoopFeatureGate`, `NullSubscriberResolver`, and the new interfaces are auto-registered. We just need explicit aliases for the interfaces so consumers can autowire them.

- [ ] **Step 1: Update `services.php` to add interface aliases**

Replace the existing file contents with:

```php
// vendor/solidworx/platform/src/Bundle/Platform/Resources/config/services.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidWorx\Platform\PlatformBundle\Controller\Security\Login;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NullSubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\PlatformBundle\SolidWorxPlatformBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services
        ->load(SolidWorxPlatformBundle::NAMESPACE . '\\', dirname(__DIR__, 2))
        ->exclude(dirname(__DIR__, 2) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services->set(Login::class)
        ->tag('controller.service_arguments');

    $services->alias(FeatureGate::class, NoopFeatureGate::class);
    $services->alias(SubscriberResolver::class, NullSubscriberResolver::class);
};
```

- [ ] **Step 2: Boot the SolidInvoice kernel and verify the gate resolves**

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice
bin/console debug:container "SolidWorx\\Platform\\PlatformBundle\\Feature\\FeatureGate"
```

Expected: shows the alias pointing to `NoopFeatureGate` (will be overridden in Task 9 once SaasBundle wires its own).

- [ ] **Step 3: Run the SolidInvoice test suite to confirm wiring is sane**

```bash
bin/phpunit --testsuite=unit
```

Expected: green (no functional change to existing behaviour).

- [ ] **Step 4: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Wire FeatureGate and SubscriberResolver defaults in PlatformBundle"
```

---

## Phase 2 — SaasBundle: plan-driven implementation

### Task 8: Add `getConfigDefault` helper to `PlanFeatureManager`

**Files:**
- Modify: `vendor/solidworx/platform/src/Bundle/Saas/Feature/PlanFeatureManager.php`
- Modify: `vendor/solidworx/platform/tests/Bundle/Saas/Feature/PlanFeatureManagerTest.php`

This helper exposes a feature's configured default value without requiring a `Plan` argument — used by `PlanFeatureGate` when no subscriber is in context.

- [ ] **Step 1: Add a failing test for the new helper**

Existing `setUp` registers three features in `FeatureConfigRegistry`: `max_users` (integer, default 10), `api_access` (boolean), `storage_gb` (integer). Reuse `max_users` for the assertion. Append inside the existing test class:

```php
public function testGetConfigDefaultReturnsRegistryDefault(): void
{
    $value = $this->manager->getConfigDefault('max_users');

    self::assertSame('max_users', $value->key);
    self::assertSame(10, $value->value);
}

public function testGetConfigDefaultThrowsForUnknownKey(): void
{
    $this->expectException(\SolidWorx\Platform\SaasBundle\Exception\UndefinedFeatureException::class);

    $this->manager->getConfigDefault('does_not_exist');
}
```

(If `$this->manager` is named differently in the existing class — e.g. a local in each test — adapt the assertion to instantiate `PlanFeatureManager` the same way other tests in the file do.)

- [ ] **Step 2: Run the new tests, expect failure**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit --filter "testGetConfigDefault" tests/Bundle/Saas/Feature/PlanFeatureManagerTest.php
```

Expected: FAIL with "Method \"getConfigDefault\" does not exist on class \"PlanFeatureManager\"".

- [ ] **Step 3: Add the helper to `PlanFeatureManager`**

In `vendor/solidworx/platform/src/Bundle/Saas/Feature/PlanFeatureManager.php`, add this method (place it next to the other public read methods, e.g. immediately after `getAllFeatures`):

```php
/**
 * Get a feature's configured default value, without consulting any plan-specific override.
 *
 * Used when no subscriber/plan is in context (e.g. CLI commands, message handlers).
 *
 * @throws UndefinedFeatureException If the feature is not defined in config.
 */
public function getConfigDefault(string $featureKey): FeatureValue
{
    return $this->configRegistry->get($featureKey)->toFeatureValue();
}
```

`FeatureValue` is already imported via the changes from Task 2.

- [ ] **Step 4: Run the new tests, expect pass**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit --filter "testGetConfigDefault" tests/Bundle/Saas/Feature/PlanFeatureManagerTest.php
```

Expected: PASS (2 new tests).

- [ ] **Step 5: Run full feature test suite to confirm no regression**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit --filter Feature
```

Expected: green.

- [ ] **Step 6: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Add PlanFeatureManager::getConfigDefault helper"
```

---

### Task 9: Add `PlanFeatureGate` and override the default

**Files:**
- Create: `vendor/solidworx/platform/src/Bundle/Saas/Feature/PlanFeatureGate.php`
- Create: `vendor/solidworx/platform/tests/Bundle/Saas/Feature/PlanFeatureGateTest.php`
- Modify: `vendor/solidworx/platform/src/Bundle/Saas/Resources/config/services.php`

- [ ] **Step 1: Write the failing test**

```php
// vendor/solidworx/platform/tests/Bundle/Saas/Feature/PlanFeatureGateTest.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\Tests\Bundle\Saas\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureValue;
use SolidWorx\Platform\PlatformBundle\Feature\NullSubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureGate;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureManager;
use Symfony\Component\Uid\Ulid;

final class PlanFeatureGateTest extends TestCase
{
    private PlanFeatureManager&MockObject $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(PlanFeatureManager::class);
    }

    public function testResolveWithExplicitSubscriberDelegatesToManager(): void
    {
        $subscriber = $this->subscriber();
        $expected = new FeatureValue('max_clients', FeatureType::INTEGER, 50);

        $this->manager->expects(self::once())
            ->method('getFeatureForSubscriber')
            ->with($subscriber, 'max_clients')
            ->willReturn($expected);

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());

        self::assertSame($expected, $gate->resolve('max_clients', $subscriber));
    }

    public function testResolveWithoutSubscriberFallsBackToConfigDefault(): void
    {
        $expected = new FeatureValue('max_clients', FeatureType::INTEGER, 5);

        $this->manager->expects(self::once())
            ->method('getConfigDefault')
            ->with('max_clients')
            ->willReturn($expected);

        $this->manager->expects(self::never())->method('getFeatureForSubscriber');

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());

        self::assertSame($expected, $gate->resolve('max_clients'));
    }

    public function testResolveUsesResolverWhenSubscriberOmitted(): void
    {
        $subscriber = $this->subscriber();
        $expected = new FeatureValue('custom_branding', FeatureType::BOOLEAN, true);

        $resolver = $this->createMock(SubscriberResolver::class);
        $resolver->expects(self::once())->method('resolve')->willReturn($subscriber);

        $this->manager->expects(self::once())
            ->method('getFeatureForSubscriber')
            ->with($subscriber, 'custom_branding')
            ->willReturn($expected);

        $gate = new PlanFeatureGate($this->manager, $resolver);

        self::assertSame($expected, $gate->resolve('custom_branding'));
    }

    public function testIsEnabledDelegatesToFeatureValue(): void
    {
        $value = new FeatureValue('flag', FeatureType::BOOLEAN, true);
        $this->manager->method('getConfigDefault')->willReturn($value);

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());

        self::assertTrue($gate->isEnabled('flag'));
    }

    public function testCanUseDelegatesToFeatureValueAllows(): void
    {
        $value = new FeatureValue('max_clients', FeatureType::INTEGER, 5);
        $this->manager->method('getConfigDefault')->willReturn($value);

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());

        self::assertTrue($gate->canUse('max_clients', 4));
        self::assertFalse($gate->canUse('max_clients', 5));
    }

    public function testRemainingDelegatesToFeatureValue(): void
    {
        $value = new FeatureValue('max_clients', FeatureType::INTEGER, 5);
        $this->manager->method('getConfigDefault')->willReturn($value);

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());

        self::assertSame(2, $gate->remaining('max_clients', 3));
    }

    public function testUpgradeOptionsMapsPlansToReferences(): void
    {
        $plan = new Plan();
        $reflection = new \ReflectionClass($plan);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($plan, new Ulid());
        $namesProp = $reflection->getProperty('name');
        $namesProp->setValue($plan, 'Pro');

        $this->manager->expects(self::once())
            ->method('findPlansWithFeature')
            ->with('custom_branding')
            ->willReturn([$plan]);

        $gate = new PlanFeatureGate($this->manager, new NullSubscriberResolver());
        $options = $gate->upgradeOptions('custom_branding');

        self::assertFalse($options->isEmpty());
        self::assertCount(1, $options->plans);
        self::assertSame('Pro', $options->plans[0]->name);
        self::assertSame($plan->getId()->toBase58(), $options->plans[0]->id);
    }

    private function subscriber(): SubscribableInterface
    {
        return new class implements SubscribableInterface {};
    }
}
```

(If `Plan`'s `id` / `name` properties aren't directly accessible via reflection — check the entity — substitute the equivalent mutator. Look at `vendor/solidworx/platform/src/Bundle/Saas/Entity/Plan.php` for the actual setters.)

- [ ] **Step 2: Run the test, expect failure**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/Saas/Feature/PlanFeatureGateTest.php
```

Expected: FAIL with "Class \"SolidWorx\\Platform\\SaasBundle\\Feature\\PlanFeatureGate\" not found".

- [ ] **Step 3: Implement `PlanFeatureGate`**

```php
// vendor/solidworx/platform/src/Bundle/Saas/Feature/PlanFeatureGate.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\SaasBundle\Feature;

use Override;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureValue;
use SolidWorx\Platform\PlatformBundle\Feature\PlanReference;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\UpgradeOptions;
use SolidWorx\Platform\SaasBundle\Entity\Plan;

final readonly class PlanFeatureGate implements FeatureGate
{
    public function __construct(
        private PlanFeatureManager $manager,
        private SubscriberResolver $resolver,
    ) {
    }

    #[Override]
    public function resolve(string $featureKey, ?SubscribableInterface $for = null): FeatureValue
    {
        $for ??= $this->resolver->resolve();

        return $for === null
            ? $this->manager->getConfigDefault($featureKey)
            : $this->manager->getFeatureForSubscriber($for, $featureKey);
    }

    #[Override]
    public function isEnabled(string $featureKey, ?SubscribableInterface $for = null): bool
    {
        return $this->resolve($featureKey, $for)->isEnabled();
    }

    #[Override]
    public function canUse(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): bool
    {
        return $this->resolve($featureKey, $for)->allows($currentUsage);
    }

    #[Override]
    public function remaining(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int
    {
        return $this->resolve($featureKey, $for)->getRemainingQuota($currentUsage);
    }

    #[Override]
    public function upgradeOptions(string $featureKey): UpgradeOptions
    {
        $references = array_map(
            static fn (Plan $plan): PlanReference => new PlanReference(
                $plan->getId()->toBase58(),
                $plan->getName(),
            ),
            $this->manager->findPlansWithFeature($featureKey),
        );

        return new UpgradeOptions($references);
    }
}
```

- [ ] **Step 4: Override the alias in SaasBundle's services config**

Edit `vendor/solidworx/platform/src/Bundle/Saas/Resources/config/services.php` to add the FeatureGate alias override at the bottom:

```php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureGate;
use SolidWorx\Platform\SaasBundle\SolidWorxPlatformSaasBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services
        ->load(SolidWorxPlatformSaasBundle::NAMESPACE . '\\', dirname(__DIR__, 2))
        ->exclude(dirname(__DIR__, 2) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services->alias(FeatureGate::class, PlanFeatureGate::class);
};
```

- [ ] **Step 5: Run the test, expect pass**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/Saas/Feature/PlanFeatureGateTest.php
```

Expected: PASS.

- [ ] **Step 6: Verify wiring boots in SolidInvoice (SaaS bundle is registered, so the alias should now point to PlanFeatureGate)**

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice
bin/console debug:container "SolidWorx\\Platform\\PlatformBundle\\Feature\\FeatureGate"
```

Expected: alias resolves to `SolidWorx\Platform\SaasBundle\Feature\PlanFeatureGate`.

- [ ] **Step 7: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Add PlanFeatureGate and override FeatureGate alias in SaasBundle"
```

---

### Task 10: Move Twig extension and runtime to PlatformBundle with new function names

**Files:**
- Move: `vendor/solidworx/platform/src/Bundle/Saas/Twig/Extension/FeatureExtension.php` → `vendor/solidworx/platform/src/Bundle/Platform/Twig/Extension/FeatureExtension.php`
- Move: `vendor/solidworx/platform/src/Bundle/Saas/Twig/Runtime/FeatureRuntime.php` → `vendor/solidworx/platform/src/Bundle/Platform/Twig/Runtime/FeatureRuntime.php`
- Move tests: `vendor/solidworx/platform/tests/Bundle/Saas/Twig/...` → `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Twig/...`

The runtime now depends on `FeatureGate` (interface) instead of `PlanFeatureManager`. Function names change to `feature_enabled`, `feature_can_use`, `feature_remaining`, `feature_unlimited`, `feature_upgrade`.

- [ ] **Step 1: Replace the runtime with the gate-backed version**

Create new file:

```php
// vendor/solidworx/platform/src/Bundle/Platform/Twig/Runtime/FeatureRuntime.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Twig\Runtime;

use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\UpgradeOptions;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class FeatureRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private FeatureGate $gate,
    ) {
    }

    public function isEnabled(string $featureKey, ?SubscribableInterface $for = null): bool
    {
        return $this->gate->isEnabled($featureKey, $for);
    }

    public function canUse(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): bool
    {
        return $this->gate->canUse($featureKey, $currentUsage, $for);
    }

    public function remaining(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int
    {
        return $this->gate->remaining($featureKey, $currentUsage, $for);
    }

    public function isUnlimited(string $featureKey, ?SubscribableInterface $for = null): bool
    {
        return $this->gate->resolve($featureKey, $for)->isUnlimited();
    }

    public function upgradeOptions(string $featureKey): UpgradeOptions
    {
        return $this->gate->upgradeOptions($featureKey);
    }
}
```

- [ ] **Step 2: Replace the extension with the renamed-functions version**

```php
// vendor/solidworx/platform/src/Bundle/Platform/Twig/Extension/FeatureExtension.php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\PlatformBundle\Twig\Extension;

use Override;
use SolidWorx\Platform\PlatformBundle\Twig\Runtime\FeatureRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FeatureExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [FeatureRuntime::class, 'isEnabled']),
            new TwigFunction('feature_can_use', [FeatureRuntime::class, 'canUse']),
            new TwigFunction('feature_remaining', [FeatureRuntime::class, 'remaining']),
            new TwigFunction('feature_unlimited', [FeatureRuntime::class, 'isUnlimited']),
            new TwigFunction('feature_upgrade', [FeatureRuntime::class, 'upgradeOptions']),
        ];
    }
}
```

- [ ] **Step 3: Delete the old SaasBundle Twig files**

```bash
rm vendor/solidworx/platform/src/Bundle/Saas/Twig/Extension/FeatureExtension.php
rm vendor/solidworx/platform/src/Bundle/Saas/Twig/Runtime/FeatureRuntime.php
rmdir vendor/solidworx/platform/src/Bundle/Saas/Twig/Extension 2>/dev/null || true
rmdir vendor/solidworx/platform/src/Bundle/Saas/Twig/Runtime 2>/dev/null || true
rmdir vendor/solidworx/platform/src/Bundle/Saas/Twig 2>/dev/null || true
```

- [ ] **Step 4: Move the existing tests to PlatformBundle test tree and rewrite them**

Delete the old extension/runtime tests and create new ones at `vendor/solidworx/platform/tests/Bundle/PlatformBundle/Twig/Runtime/FeatureRuntimeTest.php`:

```php
<?php

declare(strict_types=1);

/*
 * This file is part of SolidWorx Platform project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidWorx\Platform\Tests\Bundle\PlatformBundle\Twig\Runtime;

use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Platform\PlatformBundle\Twig\Runtime\FeatureRuntime;

final class FeatureRuntimeTest extends TestCase
{
    public function testRuntimeBackedByNoopGateAlwaysReportsAvailable(): void
    {
        $runtime = new FeatureRuntime(new NoopFeatureGate());

        self::assertTrue($runtime->isEnabled('any_key'));
        self::assertTrue($runtime->canUse('any_key', 1_000));
        self::assertNull($runtime->remaining('any_key'));
        self::assertTrue($runtime->isUnlimited('any_key'));
        self::assertTrue($runtime->upgradeOptions('any_key')->isEmpty());
    }
}
```

Delete (no longer applicable):

```bash
rm -rf vendor/solidworx/platform/tests/Bundle/Saas/Twig
```

- [ ] **Step 5: Run the new tests**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit tests/Bundle/PlatformBundle/Twig/
```

Expected: PASS.

- [ ] **Step 6: Run full vendor test suite to confirm no regression**

```bash
cd vendor/solidworx/platform && vendor/bin/phpunit
```

Expected: green.

- [ ] **Step 7: Run SolidInvoice cache:clear to validate Twig wiring boots**

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice && bin/console cache:clear
```

Expected: success, no errors about missing Twig functions or services.

- [ ] **Step 8: Commit (in the platform repo)**

```bash
cd vendor/solidworx/platform
git add -A
git commit -m "Move Feature Twig extension to PlatformBundle and back with FeatureGate"
```

---

## Phase 3 — SolidInvoice: subscriber resolver

### Task 11: Add `CompanySubscriberResolver` and wire it

**Files:**
- Create: `src/CoreBundle/Company/CompanySubscriberResolver.php`
- Create: `src/CoreBundle/Tests/Company/CompanySubscriberResolverTest.php`
- Modify (autowire alias): `src/CoreBundle/Resources/config/services/services.php`

`Company` already implements `SubscribableInterface` (the namespace was updated in Task 3). `CompanySelector::getCompany()` returns `?Ulid`. The resolver wires the two together.

- [ ] **Step 1: Write the failing test**

```php
// src/CoreBundle/Tests/Company/CompanySubscriberResolverTest.php
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

namespace SolidInvoice\CoreBundle\Tests\Company;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Component\Uid\Ulid;

final class CompanySubscriberResolverTest extends TestCase
{
    private CompanySelector&MockObject $selector;
    private CompanyRepository&MockObject $repository;
    private CompanySubscriberResolver $resolver;

    protected function setUp(): void
    {
        $this->selector = $this->createMock(CompanySelector::class);
        $this->repository = $this->createMock(CompanyRepository::class);
        $this->resolver = new CompanySubscriberResolver($this->selector, $this->repository);
    }

    public function testReturnsNullWhenNoCompanyInContext(): void
    {
        $this->selector->method('getCompany')->willReturn(null);
        $this->repository->expects(self::never())->method('find');

        self::assertNull($this->resolver->resolve());
    }

    public function testReturnsCompanyWhenSelectorHasUlid(): void
    {
        $id = new Ulid();
        $company = new Company();

        $this->selector->method('getCompany')->willReturn($id);
        $this->repository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($company);

        self::assertSame($company, $this->resolver->resolve());
    }

    public function testReturnsNullWhenRepositoryCannotFindCompany(): void
    {
        $id = new Ulid();
        $this->selector->method('getCompany')->willReturn($id);
        $this->repository->method('find')->willReturn(null);

        self::assertNull($this->resolver->resolve());
    }
}
```

- [ ] **Step 2: Run the test, expect failure**

```bash
cd /Users/pierre/projects/SolidWorx/SolidInvoice/SolidInvoice
bin/phpunit src/CoreBundle/Tests/Company/CompanySubscriberResolverTest.php
```

Expected: FAIL with "Class \"SolidInvoice\\CoreBundle\\Company\\CompanySubscriberResolver\" not found".

- [ ] **Step 3: Implement the resolver**

```php
// src/CoreBundle/Company/CompanySubscriberResolver.php
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

namespace SolidInvoice\CoreBundle\Company;

use Override;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;

final readonly class CompanySubscriberResolver implements SubscriberResolver
{
    public function __construct(
        private CompanySelector $selector,
        private CompanyRepository $repository,
    ) {
    }

    #[Override]
    public function resolve(): ?SubscribableInterface
    {
        $id = $this->selector->getCompany();

        return $id === null ? null : $this->repository->find($id);
    }
}
```

- [ ] **Step 4: Run the test, expect pass**

```bash
bin/phpunit src/CoreBundle/Tests/Company/CompanySubscriberResolverTest.php
```

Expected: PASS (3 tests).

- [ ] **Step 5: Wire the resolver as the `SubscriberResolver` alias**

Edit `src/CoreBundle/Resources/config/services/services.php`. Autoload picks up `CompanySubscriberResolver` automatically. Add the use statements at the top alongside the existing `use` block, then add the alias inside the `return static function` body:

```php
// Add to existing use statements at the top of the file:
use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;

// Add inside the configurator body (e.g. immediately before the closing `};`):
$services = $containerConfigurator->services();
$services->alias(SubscriberResolver::class, CompanySubscriberResolver::class);
```

(If `$services` is already in scope earlier in the function, just append the alias line — don't re-declare.)

- [ ] **Step 6: Verify the alias overrides PlatformBundle's null resolver**

```bash
bin/console debug:container "SolidWorx\\Platform\\PlatformBundle\\Feature\\SubscriberResolver"
```

Expected: alias resolves to `SolidInvoice\CoreBundle\Company\CompanySubscriberResolver`.

- [ ] **Step 7: Run code-quality tools to confirm no regressions**

```bash
bin/ecs check --fix src/CoreBundle/Company/CompanySubscriberResolver.php
bin/phpstan analyse src/CoreBundle/Company/CompanySubscriberResolver.php
```

Expected: clean.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add CompanySubscriberResolver to plug PlatformBundle FeatureGate into SolidInvoice"
```

---

### Task 12: End-to-end smoke test

**Files:**
- Create: `src/CoreBundle/Tests/Functional/FeatureGateWiringTest.php`

This test boots the kernel and confirms the wired services are what we expect — `FeatureGate` resolves to `PlanFeatureGate` (because SaasBundle is loaded in SolidInvoice), `SubscriberResolver` resolves to `CompanySubscriberResolver`, and asking the gate about an undefined feature key with no company in context returns the registry default (or — if no SaaS features are registered — fails predictably).

- [ ] **Step 1: Write the smoke test**

```php
// src/CoreBundle/Tests/Functional/FeatureGateWiringTest.php
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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FeatureGateWiringTest extends KernelTestCase
{
    public function testFeatureGateAliasResolvesToPlanFeatureGate(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has(FeatureGate::class));
        self::assertInstanceOf(PlanFeatureGate::class, $container->get(FeatureGate::class));
    }

    public function testSubscriberResolverAliasResolvesToCompanyResolver(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has(SubscriberResolver::class));
        self::assertInstanceOf(CompanySubscriberResolver::class, $container->get(SubscriberResolver::class));
    }
}
```

The `self::getContainer()` idiom matches existing CoreBundle/InvoiceBundle functional tests.

- [ ] **Step 2: Run the smoke test**

```bash
bin/phpunit src/CoreBundle/Tests/Functional/FeatureGateWiringTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 3: Run the full SolidInvoice test suite as a final sanity check**

```bash
bin/phpunit
```

Expected: green. (No new test should affect existing suites — the wiring is additive.)

- [ ] **Step 4: Run static analysis across the touched paths**

```bash
bin/phpstan analyse src/CoreBundle/Company src/CoreBundle/Tests/Company src/CoreBundle/Tests/Functional/FeatureGateWiringTest.php
```

Expected: clean.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add functional smoke test for FeatureGate and SubscriberResolver wiring"
```

---

## Out of scope (do NOT implement in this plan)

These are explicitly future work, listed for clarity:

- **Defining specific features.** No `Features` constants class, no `config/packages/solidworx_platform_saas.php` feature definitions, no DB seed data for `PlanFeature` rows.
- **Gating any existing functionality.** No call sites are added that check `$gate->isEnabled(...)`.
- **Removing the `solidworx/toggler` `saas_enabled` flag.** Orthogonal — that toggle gates UI like the subscription menu, not feature limits.
- **DB-driven feature definitions.** The current config-based registry is used as-is.

When the first real feature is gated (a follow-up task), the workflow will be: declare the feature in `solidworx_platform_saas.features` config, add a constant in a `Features` class, optionally seed `PlanFeature` rows for non-default plans, and add `$gate->isEnabled(Features::X)` / `$gate->canUse(Features::X, $count)` checks at call sites.
