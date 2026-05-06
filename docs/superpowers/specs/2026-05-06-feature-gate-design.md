# Feature Gate Infrastructure — Design

**Date:** 2026-05-06
**Status:** Approved (pending written-spec review)
**Scope:** SolidInvoice + SolidWorx Platform (vendor)

## 1. Problem

SolidInvoice ships in two flavours:

- **Self-hosted** — every feature available, no limits, minimum runtime overhead.
- **Hosted SaaS** — feature availability and quantitative limits driven by the user's subscription plan.

The Platform `SaasBundle` already provides `PlanFeatureManager`, `FeatureValue`, `PlanFeatureToggle`, and a Twig extension. These work, but they live in `SaasBundle` and require explicit subscriber arguments. Using them directly from non-SaaS bundles would either (a) leak SaaS imports throughout the codebase or (b) require every call site to wrap checks in `if ($toggler->isActive('saas_enabled'))`.

We need an abstraction that:

1. Lets any bundle (CoreBundle, ClientBundle, InvoiceBundle, …) check feature availability and quantitative limits without importing SaaS-specific classes.
2. Returns "available, unlimited" in self-hosted with zero configuration.
3. Returns plan-driven values in SaaS deployments.
4. Supports upgrade-path messaging ("Upgrade to plan X to unlock this") without leaking SaaS into call sites.
5. Works in PHP (services, Actions, form types) and in Twig templates.

This design defines the infrastructure only. **No actual feature gates are added.** Specific features (e.g. `max_clients`, `custom_branding`) and their call-site checks come in later work.

## 2. Decisions

| # | Decision |
|---|---|
| D1 | Gate API supports both boolean availability and integer quantitative limits (and string/array values for future-proofing). |
| D2 | Self-hosted always returns "available, unlimited" — no env vars, no config. |
| D3 | The contract (interface + DTOs + value objects) lives in `PlatformBundle`. The plan-driven implementation lives in `SaasBundle`. |
| D4 | First-class call-site support: PHP services, Twig, Form types. (Voters, Doctrine listeners, API Platform are not first-class but can use the same PHP API.) |
| D5 | Implicit current subject (the company), explicit override available. The implicit subject is resolved via a `SubscriberResolver` provided by SolidInvoice (uses `CompanySelector`). |
| D6 | Upgrade-path info is exposed by the gate itself (`upgradeOptions()`), returning a plain DTO that is empty in self-hosted. |
| D7 | Feature definitions remain config-driven (Platform's existing `FeatureConfigRegistry`); per-plan overrides remain DB-driven (`PlanFeature` entity). No change to that split. |
| D8 | No backwards-compatibility layer. The Platform feature APIs are new and have no external consumers; classes are renamed and moved cleanly. |

## 3. Architecture

```
                          ┌─────────────────────────────────────────┐
                          │          PlatformBundle (vendor)        │
                          │                                         │
                          │  Feature\FeatureGate          (iface)   │
                          │  Feature\NoopFeatureGate      (default) │◄── self-hosted uses this
                          │  Feature\FeatureValue                   │
                          │  Feature\FeatureType          (enum)    │
                          │  Feature\SubscribableInterface          │
                          │  Feature\SubscriberResolver   (iface)   │
                          │  Feature\NullSubscriberResolver         │
                          │  Feature\UpgradeOptions       (DTO)     │
                          │  Feature\PlanReference        (DTO)     │
                          │  Twig\Extension\FeatureExtension        │
                          │  Twig\Runtime\FeatureRuntime            │
                          └─────────────────────────────────────────┘
                                            ▲
                                            │ implements / overrides
                          ┌─────────────────┴───────────────────────┐
                          │            SaasBundle (vendor)          │
                          │                                         │
                          │  Feature\PlanFeatureGate                │◄── SaaS overrides default
                          │  Feature\PlanFeatureManager  (existing) │
                          │  Feature\FeatureConfigRegistry (existing)│
                          │  Feature\PlanFeatureToggle   (existing) │
                          └─────────────────────────────────────────┘

                          ┌─────────────────────────────────────────┐
                          │         SolidInvoice (this repo)        │
                          │                                         │
                          │  CoreBundle\Company\CompanySubscriberResolver
                          │  CoreBundle\Entity\Company  (marker iface)
                          └─────────────────────────────────────────┘
```

Service binding rule: PlatformBundle binds `FeatureGate::class` → `NoopFeatureGate` and `SubscriberResolver::class` → `NullSubscriberResolver` as defaults. SaasBundle overrides `FeatureGate::class` → `PlanFeatureGate` when loaded. SolidInvoice's `CoreBundle` overrides `SubscriberResolver::class` → `CompanySubscriberResolver`.

## 4. The Contract

### 4.1 `FeatureGate` (PlatformBundle)

```php
namespace SolidWorx\Platform\PlatformBundle\Feature;

interface FeatureGate
{
    /** Resolve the full feature value (enabled, limit, type, raw value). */
    public function resolve(string $featureKey, ?SubscribableInterface $for = null): FeatureValue;

    /** Convenience: is the feature enabled for this subject? */
    public function isEnabled(string $featureKey, ?SubscribableInterface $for = null): bool;

    /** Convenience: can the subject use one more, given current usage? */
    public function canUse(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): bool;

    /** Convenience: how many remaining (null = unlimited or non-quantitative). */
    public function remaining(string $featureKey, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int;

    /** Upgrade guidance when a feature is unavailable. Empty in self-hosted. */
    public function upgradeOptions(string $featureKey): UpgradeOptions;
}
```

When `$for` is `null`, the implementation resolves the current subject via the injected `SubscriberResolver`.

### 4.2 `NoopFeatureGate` (PlatformBundle, default)

Always returns "available, unlimited":

- `resolve()` → a synthetic `FeatureValue` with `FeatureType::INTEGER`, value `FeatureValue::UNLIMITED` (-1) for any key.
- `isEnabled()` → `true`.
- `canUse()` → `true`.
- `remaining()` → `null` (unlimited).
- `upgradeOptions()` → `new UpgradeOptions([])`.

The no-op never consults a registry — it doesn't know what features exist, and that's intentional. Self-hosted has no opinion about feature keys; everything passes.

### 4.3 `PlanFeatureGate` (SaasBundle)

```php
final readonly class PlanFeatureGate implements FeatureGate
{
    public function __construct(
        private PlanFeatureManager $manager,
        private SubscriberResolver $resolver,
    ) {}

    public function resolve(string $key, ?SubscribableInterface $for = null): FeatureValue
    {
        $for ??= $this->resolver->resolve();

        return $for === null
            ? $this->manager->getConfigDefault($key)
            : $this->manager->getFeatureForSubscriber($for, $key);
    }

    public function isEnabled(string $key, ?SubscribableInterface $for = null): bool
    {
        return $this->resolve($key, $for)->isEnabled();
    }

    public function canUse(string $key, int $currentUsage = 0, ?SubscribableInterface $for = null): bool
    {
        return $this->resolve($key, $for)->allows($currentUsage);
    }

    public function remaining(string $key, int $currentUsage = 0, ?SubscribableInterface $for = null): ?int
    {
        return $this->resolve($key, $for)->getRemainingQuota($currentUsage);
    }

    public function upgradeOptions(string $key): UpgradeOptions
    {
        $plans = array_map(
            static fn (Plan $p) => new PlanReference($p->getId()->toBase58(), $p->getName()),
            $this->manager->findPlansWithFeature($key),
        );

        return new UpgradeOptions($plans);
    }
}
```

A new helper is added to `PlanFeatureManager`:

```php
public function getConfigDefault(string $featureKey): FeatureValue
{
    return $this->configRegistry->get($featureKey)->toFeatureValue();
}
```

This is used when no subscriber is in context (e.g. CLI, message handlers without an explicit subject) — the call returns the configured default rather than throwing.

`UndefinedFeatureException` thrown by `PlanFeatureManager` for unknown keys is allowed to propagate from `PlanFeatureGate` — this is a developer error and should fail loudly. (The existing `hasFeature()` swallow-and-return-false behaviour on `PlanFeatureManager` itself is unchanged for the convenience of other callers.)

### 4.4 DTOs (PlatformBundle)

```php
final readonly class UpgradeOptions
{
    /** @param list<PlanReference> $plans */
    public function __construct(public array $plans) {}

    public function isEmpty(): bool
    {
        return $this->plans === [];
    }
}

final readonly class PlanReference
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
```

These DTOs intentionally carry the minimum needed for UI messaging. The `Plan` entity is never returned from the gate — call sites in non-SaaS bundles never see SaaS classes.

## 5. Subscriber Resolution

### 5.1 `SubscriberResolver` (PlatformBundle)

```php
interface SubscriberResolver
{
    /** Returns the current subject for an implicit gate check, or null if none. */
    public function resolve(): ?SubscribableInterface;
}
```

PlatformBundle ships `NullSubscriberResolver` (always returns `null`) as the default binding.

### 5.2 `CompanySubscriberResolver` (SolidInvoice CoreBundle)

```php
namespace SolidInvoice\CoreBundle\Company;

final readonly class CompanySubscriberResolver implements SubscriberResolver
{
    public function __construct(
        private CompanySelector $selector,
        private CompanyRepository $companies,
    ) {}

    public function resolve(): ?SubscribableInterface
    {
        $id = $this->selector->getCompany();

        return $id === null ? null : $this->companies->find($id);
    }
}
```

`CompanySelector::getCompany()` already exists and returns the current company's `?Ulid`. The `Company` entity must implement `SubscribableInterface` (a marker interface, no methods required).

### 5.3 Behaviour when no subscriber is in context

- `NoopFeatureGate` doesn't care — always available/unlimited.
- `PlanFeatureGate` falls back to `PlanFeatureManager::getConfigDefault()`, which returns the un-overridden registry default. This makes CLI commands, message handlers, and fixtures safe — they don't crash, they see baseline values.

Call sites running outside a request context can pass an explicit `$for` when they have the subject, or accept the fallback when they don't.

## 6. Twig Integration

The Twig extension and runtime move from `SaasBundle\Twig` to `PlatformBundle\Twig`, so the functions are always available regardless of whether SaasBundle is loaded. The runtime delegates to the autowired `FeatureGate` (no-op or plan-backed depending on bundle wiring).

### 6.1 Function inventory (final names)

| Function | Returns | Notes |
|---|---|---|
| `feature_enabled(key, for=null)` | `bool` | Implicit current subject when `for` omitted. |
| `feature_can_use(key, usage=0, for=null)` | `bool` | Quota check against current usage. |
| `feature_remaining(key, usage=0, for=null)` | `?int` | `null` means unlimited. |
| `feature_unlimited(key, for=null)` | `bool` | True when limit is `FeatureValue::UNLIMITED`. |
| `feature_upgrade(key)` | `UpgradeOptions` | Empty in self-hosted; non-empty when SaaS has plans offering this feature. |

Old names (`has_feature`, `feature_value`, `can_use_feature`, `feature_remaining`, `is_feature_unlimited`) are removed. There is no BC layer — these functions have no production consumers yet.

### 6.2 Example template usage

```twig
{% if not feature_enabled('custom_branding') %}
  {% set upgrade = feature_upgrade('custom_branding') %}
  {% if upgrade.plans is not empty %}
    <div class="upgrade-nudge">
      Upgrade to {{ upgrade.plans|first.name }} to enable custom branding.
    </div>
  {% endif %}
{% endif %}
```

In self-hosted, `feature_enabled` is always `true`, the outer block is skipped, no `saas_enabled` guard required.

## 7. Service Wiring

### 7.1 PlatformBundle (vendor)

`src/Bundle/Platform/Resources/config/services.php`:

- `FeatureGate::class` autoconfigured; `NoopFeatureGate` registered and aliased to `FeatureGate::class` as the default.
- `SubscriberResolver::class` autoconfigured; `NullSubscriberResolver` registered and aliased to `SubscriberResolver::class` as the default.
- `FeatureExtension` registered as a Twig extension; `FeatureRuntime` registered as a Twig runtime.

### 7.2 SaasBundle (vendor)

`src/Bundle/Saas/Resources/config/services.php`:

- `PlanFeatureGate` registered; alias `FeatureGate::class` → `PlanFeatureGate` (overrides Platform's no-op).
- Existing `PlanFeatureManager`, `FeatureConfigRegistry`, `PlanFeatureToggle` services unchanged in shape; only namespaces of moved classes (`FeatureValue`, `FeatureType`, `SubscribableInterface`) update in their `use` statements.

### 7.3 SolidInvoice CoreBundle

`src/CoreBundle/Resources/config/services.php`:

- `CompanySubscriberResolver` registered; alias `SubscriberResolver::class` → `CompanySubscriberResolver` (overrides Platform's null resolver).
- `Company` entity gets `implements SubscribableInterface` (marker only — no method body changes).

## 8. Bundle File Layout

### 8.1 PlatformBundle (added/moved)

```
src/Bundle/Platform/Feature/
├── FeatureGate.php                 # NEW — interface
├── NoopFeatureGate.php             # NEW — default impl
├── FeatureValue.php                # MOVED from SaasBundle\Feature
├── FeatureType.php                 # MOVED from SaasBundle\Enum
├── SubscribableInterface.php       # MOVED from SaasBundle\Subscriber
├── SubscriberResolver.php          # NEW — interface
├── NullSubscriberResolver.php      # NEW — default impl
├── UpgradeOptions.php              # NEW — DTO
└── PlanReference.php               # NEW — DTO

src/Bundle/Platform/Twig/
├── Extension/FeatureExtension.php  # MOVED from SaasBundle, function names renamed
└── Runtime/FeatureRuntime.php      # MOVED from SaasBundle, signatures updated
```

### 8.2 SaasBundle (added/changed)

```
src/Bundle/Saas/Feature/
├── PlanFeatureGate.php             # NEW
├── PlanFeatureManager.php          # add getConfigDefault() helper; update use statements
├── PlanFeatureToggle.php           # update use statements only
├── FeatureConfig.php               # update use statements only
├── FeatureConfigRegistry.php       # update use statements only
└── (FeatureValue, FeatureType, SubscribableInterface — gone, moved to PlatformBundle)
```

`SaasBundle\Twig\Extension\FeatureExtension` and `SaasBundle\Twig\Runtime\FeatureRuntime` are deleted (moved to PlatformBundle).

### 8.3 SolidInvoice (added/changed)

```
src/CoreBundle/Company/CompanySubscriberResolver.php   # NEW
src/CoreBundle/Resources/config/services.php           # bind SubscriberResolver
src/CoreBundle/Entity/Company.php                      # add SubscribableInterface marker
```

No SaaS imports anywhere outside `src/SaasBundle/`.

## 9. Feature Definitions (out of scope for this design, documented for future reference)

When actual gates are added in later work:

- **Typo-safe keys** live in a `SolidInvoice\SaasBundle\Features` constants class (or similar, location TBD when first feature is added).
- **Feature metadata** (key, type, default, description) is registered via SaasBundle's existing Symfony config tree at `solidworx_platform.saas.features`. This will likely become a new file like `config/packages/solidworx_platform_saas.php` in SolidInvoice.
- **Per-plan overrides** are stored in the `PlanFeature` table (DB) and managed via `PlanFeatureManager::setFeature()` — admin UI / fixtures.

The split:

| What | Where | Deploy required to change? |
|---|---|---|
| Feature *exists* (key + type + default) | Config (`FeatureConfigRegistry`) | Yes — call sites depend on the key being defined in code anyway. |
| Feature *value per plan* | DB (`PlanFeature` rows) | No — admin can tune live. |
| Call-site checks (`$gate->isEnabled(...)`) | Code | Yes. |

This split is unchanged from current Platform behaviour; nothing in this design alters it.

## 10. Testing

### 10.1 PlatformBundle

- `NoopFeatureGateTest` — every method returns "available/unlimited/empty upgrade" for any key, with and without a subject.
- `NullSubscriberResolverTest` — returns `null`.
- `FeatureExtensionTest` — Twig functions delegate to the gate; verified with a stub `FeatureGate`.
- Existing `FeatureValue`/`FeatureType` tests follow the namespace move.

### 10.2 SaasBundle

- `PlanFeatureGateTest` — covers explicit subscriber, implicit via resolver, null resolver fallback to config defaults, `upgradeOptions` DTO mapping, all four convenience methods.
- Existing `PlanFeatureManagerTest`, `PlanFeatureToggleTest` continue to pass with namespace updates.
- New test for `PlanFeatureManager::getConfigDefault()`.

### 10.3 SolidInvoice

- `CompanySubscriberResolverTest` — given a Ulid from `CompanySelector`, returns the matching `Company`; given `null`, returns `null`.
- A small integration/smoke test in `CoreBundle/Tests` that boots the kernel and asserts the wired `FeatureGate` is `NoopFeatureGate` by default.

No tests of "feature X gates behaviour Y" yet — that comes when actual gates are added.

## 11. Migration & Rollout

This change is purely additive at the call-site level — no SolidInvoice features are gated.

1. **Vendor (PlatformBundle)** — add interfaces, no-op impls, DTOs; move `FeatureValue`, `FeatureType`, `SubscribableInterface`; move + rewrite Twig extension/runtime with new function names. Tag a release.
2. **Vendor (SaasBundle)** — add `PlanFeatureGate`; add `PlanFeatureManager::getConfigDefault()`; update use statements for moved classes; delete the now-relocated Twig extension/runtime. Tag a matching release.
3. **SolidInvoice** — bump platform constraint; add `CompanySubscriberResolver`; mark `Company` as `SubscribableInterface`; register the resolver in CoreBundle services config. No call sites are modified in this phase.
4. **Out of scope** — defining specific features and adding gate checks at call sites. Each feature is its own follow-up.

No database migration. No env var changes. No runtime behaviour changes for users until step 4.

**Risk surface:** The class moves (`FeatureValue`, `FeatureType`, `SubscribableInterface`) are breaking renames. There are no production consumers, so no BC layer is required. Any internal Platform/SaaS code referencing these classes is updated in step 1–2.

## 12. Non-Goals

- **Defining specific features.** No features are added by this work.
- **Gating any existing functionality.** Call sites are untouched.
- **DB-driven feature definitions.** The current config-based registry is sufficient; reconsidering this is YAGNI.
- **Runtime feature flags / experiments.** Use `solidworx/toggler` (already integrated) for global on/off toggles like `saas_enabled`. The feature gate is for plan-aware, per-subscriber availability.
- **Backwards compatibility.** No aliases, no deprecated names. The Platform feature APIs have no production consumers.

## 13. Open Questions

None. All design questions have been resolved through the brainstorming dialogue.
