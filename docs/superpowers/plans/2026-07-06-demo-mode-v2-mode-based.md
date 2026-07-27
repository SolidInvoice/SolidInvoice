# Demo Mode v2 — Mode-Based Architecture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax.
>
> **This plan supersedes `2026-07-05-demo-mode.md`.** That plan's Tasks 1–10 are already committed on branch `demo-mode`; this plan refactors that foundation to the mode-based architecture and finishes the remaining work. Do not re-run the old plan.

**Goal:** Replace the demo/saas boolean flags with a single `SOLIDINVOICE_MODE` enum (`self-hosted` | `demo` | `saas`), a `ModeResolver` service, and a `Capability` policy, then express every demo restriction as a capability check and every SaaS check via the resolver. Finish the demo feature (payments, credential lock, watermark, banners) against this contract.

**Architecture:** `SOLIDINVOICE_MODE` env → `ApplicationMode` enum. `ModeResolver` (CoreBundle) exposes `current()`, `is*()` predicates, `allows(Capability)`, and demo-parameter accessors. Restriction sites ask `allows(Capability::X)`; presentation keys off `isDemo()`. `SOLIDINVOICE_PLATFORM` and `SOLIDINVOICE_DEMO` are retired; `saas_enabled` toggler is redefined as `MODE==='saas'`; all runtime SaaS consumers migrate to the resolver (decision A2). Mutual exclusion is structural (one enum value), so the old boot guard is removed and replaced with boot-time value validation.

**Tech Stack:** PHP 8.4, Symfony 7.1, `solidworx/toggler`, `solidworx/platform`, Twig, Doctrine, Payum, `symfony/lock`, PHPUnit + Foundry.

## Global Constraints

- PHP 8.4+, Symfony 7.1+. `declare(strict_types=1);`, full type hints, standard license header on new PHP files (copy verbatim from a neighbouring file). Twig header `{# ... #}` variant.
- `final` classes (except Doctrine entities). Backed enums for fixed value sets.
- Quality gate per task: `bin/ecs check --fix && bin/phpstan analyse <changed files> && bin/phpunit <scoped path>`. Full-project `bin/phpstan analyse` has ~15 pre-existing unrelated errors — scope phpstan to changed files. Commit per task (conventional commits).
- **Environment quirk:** `composer` needs `COMPOSER_ALLOW_SUPERUSER=1` (already applied; re-run if `bin/phpstan` emits an "Invalid configuration" / `parameters › symfony` error).
- **Shared contract (built in Phase R, consumed everywhere — exact names):**
  - `SolidInvoice\CoreBundle\Mode\ApplicationMode` (enum: `SelfHosted='self-hosted'`, `Demo='demo'`, `Saas='saas'`).
  - `SolidInvoice\CoreBundle\Mode\Capability` (enum: `UserRegistration`, `RealEmailDelivery`, `RealNotificationDelivery`, `OnlinePaymentCapture`, `CredentialChange`).
  - `SolidInvoice\CoreBundle\Mode\ModeResolver` — `current(): ApplicationMode`, `is(ApplicationMode): bool`, `isSelfHosted(): bool`, `isDemo(): bool`, `isSaas(): bool`, `allows(Capability): bool`, `demoUsername(): ?string`, `demoPassword(): ?string`, `demoSignupUrl(): ?string`.
  - Twig fns: `app_mode(): string`, `is_demo(): bool`, `is_saas(): bool`, `demo_username(): ?string`, `demo_password(): ?string`, `demo_signup_url(): ?string`.
  - Env: `SOLIDINVOICE_MODE` (default `'self-hosted'`), `SOLIDINVOICE_DEMO_USERNAME`/`_PASSWORD`/`_SIGNUP_URL` (default `''`).
- **Testability note (supersedes the old final-class erratum):** `ModeResolver` takes its mode + demo params as plain constructor strings, so tests construct a real `new ModeResolver('demo', 'user@x', 'pw', '')` — no mocking of a final class needed anywhere. Delete the old `DemoMode`-mocking workarounds as you migrate each test.
- **Non-goals:** rate limiting (deferred, unchanged).

---

## Phase R — Mode foundation (refactors committed Tasks 1–5)

### Task R1: Introduce `ApplicationMode` + `Capability` enums and the `ModeResolver` service

**Files:**
- Create: `src/CoreBundle/Mode/ApplicationMode.php`, `src/CoreBundle/Mode/Capability.php`, `src/CoreBundle/Mode/ModeResolver.php`
- Test (Create): `src/CoreBundle/Tests/Mode/ModeResolverTest.php`

**Interfaces:**
- Produces the shared contract above. No consumers yet (wired in later tasks).

- [ ] **Step 1: Failing test.** Create `ModeResolverTest.php` (plain `TestCase`, no mocking — construct real resolvers):

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

namespace SolidInvoice\CoreBundle\Tests\Mode;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Mode\ApplicationMode;
use SolidInvoice\CoreBundle\Mode\Capability;
use SolidInvoice\CoreBundle\Mode\ModeResolver;

final class ModeResolverTest extends TestCase
{
    public function testDefaultsToSelfHostedAllowingEverything(): void
    {
        $resolver = new ModeResolver();

        self::assertSame(ApplicationMode::SelfHosted, $resolver->current());
        self::assertTrue($resolver->isSelfHosted());
        self::assertFalse($resolver->isDemo());
        foreach (Capability::cases() as $capability) {
            self::assertTrue($resolver->allows($capability), $capability->name);
        }
    }

    public function testDemoModeDeniesRestrictedCapabilities(): void
    {
        $resolver = new ModeResolver('demo', 'demo@example.com', 'secret', 'https://signup.example.com');

        self::assertTrue($resolver->isDemo());
        self::assertFalse($resolver->allows(Capability::UserRegistration));
        self::assertFalse($resolver->allows(Capability::RealEmailDelivery));
        self::assertFalse($resolver->allows(Capability::RealNotificationDelivery));
        self::assertFalse($resolver->allows(Capability::OnlinePaymentCapture));
        self::assertFalse($resolver->allows(Capability::CredentialChange));
        self::assertSame('demo@example.com', $resolver->demoUsername());
        self::assertSame('secret', $resolver->demoPassword());
        self::assertSame('https://signup.example.com', $resolver->demoSignupUrl());
    }

    public function testSaasModeAllowsEverythingAtThisLayer(): void
    {
        $resolver = new ModeResolver('saas');

        self::assertTrue($resolver->isSaas());
        foreach (Capability::cases() as $capability) {
            self::assertTrue($resolver->allows($capability), $capability->name);
        }
        self::assertNull($resolver->demoUsername());
    }

    public function testUnknownModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ModeResolver('bogus'))->current();
    }
}
```

- [ ] **Step 2: Run, expect fail** (`bin/phpunit src/CoreBundle/Tests/Mode/ModeResolverTest.php`) — classes not found.

- [ ] **Step 3: Implement the enums.** `src/CoreBundle/Mode/ApplicationMode.php`:

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

namespace SolidInvoice\CoreBundle\Mode;

enum ApplicationMode: string
{
    case SelfHosted = 'self-hosted';
    case Demo = 'demo';
    case Saas = 'saas';
}
```

`src/CoreBundle/Mode/Capability.php`:

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

namespace SolidInvoice\CoreBundle\Mode;

/**
 * Actions whose availability depends on the application run mode (see ModeResolver).
 * Distinct from solidworx/toggler (capability wired?) and the SaaS plan FeatureGate (plan includes?).
 */
enum Capability
{
    case UserRegistration;
    case RealEmailDelivery;
    case RealNotificationDelivery;
    case OnlinePaymentCapture;
    case CredentialChange;
}
```

- [ ] **Step 4: Implement `ModeResolver`.** `src/CoreBundle/Mode/ModeResolver.php`:

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

namespace SolidInvoice\CoreBundle\Mode;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function sprintf;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Mode\ModeResolverTest
 */
final class ModeResolver
{
    /**
     * Capabilities DENIED per mode. Modes not listed allow everything at this layer
     * (SaaS plan/subscription restrictions live in the separate FeatureGate layer).
     *
     * @var array<string, list<Capability>>
     */
    private const array DENIED = [
        ApplicationMode::Demo->value => [
            Capability::UserRegistration,
            Capability::RealEmailDelivery,
            Capability::RealNotificationDelivery,
            Capability::OnlinePaymentCapture,
            Capability::CredentialChange,
        ],
    ];

    public function __construct(
        #[Autowire('%env(SOLIDINVOICE_MODE)%')]
        private readonly string $mode = 'self-hosted',
        #[Autowire('%env(SOLIDINVOICE_DEMO_USERNAME)%')]
        private readonly string $demoUsername = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_PASSWORD)%')]
        private readonly string $demoPassword = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_SIGNUP_URL)%')]
        private readonly string $demoSignupUrl = '',
    ) {
    }

    public function current(): ApplicationMode
    {
        return ApplicationMode::tryFrom($this->mode)
            ?? throw new InvalidArgumentException(sprintf(
                'Invalid SOLIDINVOICE_MODE "%s". Expected one of: self-hosted, demo, saas.',
                $this->mode,
            ));
    }

    public function is(ApplicationMode $mode): bool
    {
        return $this->current() === $mode;
    }

    public function isSelfHosted(): bool
    {
        return $this->is(ApplicationMode::SelfHosted);
    }

    public function isDemo(): bool
    {
        return $this->is(ApplicationMode::Demo);
    }

    public function isSaas(): bool
    {
        return $this->is(ApplicationMode::Saas);
    }

    public function allows(Capability $capability): bool
    {
        return ! in_array($capability, self::DENIED[$this->current()->value] ?? [], true);
    }

    public function demoUsername(): ?string
    {
        return $this->isDemo() && '' !== $this->demoUsername ? $this->demoUsername : null;
    }

    public function demoPassword(): ?string
    {
        return $this->isDemo() && '' !== $this->demoPassword ? $this->demoPassword : null;
    }

    public function demoSignupUrl(): ?string
    {
        return $this->isDemo() && '' !== $this->demoSignupUrl ? $this->demoSignupUrl : null;
    }
}
```

(CoreBundle autowires/autoconfigures its namespace; `Mode/` is not excluded, so no manual registration. Confirm with `bin/console debug:container SolidInvoice\\CoreBundle\\Mode\\ModeResolver --env=test`.)

- [ ] **Step 5: Run, expect pass** (4 tests). Then quality gate on the 4 new files.

- [ ] **Step 6: Commit** — `git add src/CoreBundle/Mode src/CoreBundle/Tests/Mode && git commit -m "feat(core): add ApplicationMode/Capability enums and ModeResolver service"`

---

### Task R2: Switch env defaults + container-build-time reads to `SOLIDINVOICE_MODE`

**Files:**
- Modify: `config/services.php` (env defaults: add `SOLIDINVOICE_MODE`='self-hosted'; remove `SOLIDINVOICE_DEMO` and `SOLIDINVOICE_PLATFORM`; keep the 3 demo params)
- Modify: `config/bundles.php` (load SaaS bundles when `SOLIDINVOICE_MODE==='saas'`; boot-time validation: unknown mode → throw; `demo` without username/password → throw; remove the old demo-vs-saas guard)
- Modify: `src/Kernel.php` (saas config/route imports keyed on `SOLIDINVOICE_MODE==='saas'` — verify the current mechanism first; it may key off the SaaS bundle being registered rather than the env var directly, in which case no change is needed there)
- Modify: `config/services_test.php` (mirror the saas gate: `SOLIDINVOICE_MODE==='saas'`)
- Modify: `src/CoreBundle/Resources/config/services/services.php:81` (`!== 'saas'` gate → read `SOLIDINVOICE_MODE`)
- Test: reuse/adjust `src/CoreBundle/Tests/Functional/DemoEnvDefaultsTest.php` and `DemoSaasMutualExclusionTest.php` — the former asserts the new `SOLIDINVOICE_MODE` default; the latter is repurposed to assert unknown-mode and demo-without-creds boot failures (the "demo AND saas simultaneously" case no longer exists).

**Interfaces:**
- Consumes: env only (build-time). Produces: correct bundle loading + fail-fast validation keyed on `SOLIDINVOICE_MODE`.

- [ ] **Step 1: Read all five files first** to capture their exact current env-read expressions (they were touched by committed Tasks 1/5/6 — line numbers have shifted).

- [ ] **Step 2: Update the tests** to the new behavior (failing first):
  - `DemoEnvDefaultsTest`: assert `env(SOLIDINVOICE_MODE) === 'self-hosted'`, and the 3 demo-param defaults `=== ''`; assert `SOLIDINVOICE_DEMO` / `SOLIDINVOICE_PLATFORM` parameters are no longer defined (`$container->hasParameter(...)` is false) — proving retirement.
  - `DemoSaasMutualExclusionTest` → rename intent to `ModeBootValidationTest` (new file; delete the old one): (a) `SOLIDINVOICE_MODE=bogus` → requiring `config/bundles.php` throws; (b) `SOLIDINVOICE_MODE=demo` with empty username/password → throws; (c) `SOLIDINVOICE_MODE=demo` with creds set → returns the bundle array; (d) `SOLIDINVOICE_MODE=saas` → bundle array contains the two SaaS bundle classes. Follow the existing `$_SERVER`/`$_ENV` manipulation + `require` pattern from the current mutual-exclusion test.

- [ ] **Step 3: Run, expect fail.**

- [ ] **Step 4: Implement.**
  - `config/services.php`: replace the `env(SOLIDINVOICE_DEMO)` line with `$parameters->set('env(SOLIDINVOICE_MODE)', 'self-hosted');`; delete `$parameters->set('env(SOLIDINVOICE_PLATFORM)', null);`; keep the three `SOLIDINVOICE_DEMO_*` param defaults.
  - `config/bundles.php`: replace the current `$platform`/`$demoEnabled` block with:

```php
$mode = $_ENV['SOLIDINVOICE_MODE'] ?? $_SERVER['SOLIDINVOICE_MODE'] ?? 'self-hosted';

if (! in_array($mode, ['self-hosted', 'demo', 'saas'], true)) {
    throw new RuntimeException(sprintf('Invalid SOLIDINVOICE_MODE "%s". Expected one of: self-hosted, demo, saas.', $mode));
}

if ($mode === 'demo') {
    $demoUsername = $_ENV['SOLIDINVOICE_DEMO_USERNAME'] ?? $_SERVER['SOLIDINVOICE_DEMO_USERNAME'] ?? '';
    $demoPassword = $_ENV['SOLIDINVOICE_DEMO_PASSWORD'] ?? $_SERVER['SOLIDINVOICE_DEMO_PASSWORD'] ?? '';
    if ($demoUsername === '' || $demoPassword === '') {
        throw new RuntimeException('SOLIDINVOICE_MODE=demo requires SOLIDINVOICE_DEMO_USERNAME and SOLIDINVOICE_DEMO_PASSWORD to be set.');
    }
}

if ($mode === 'saas') {
    $bundles[SolidWorxPlatformSaasBundle::class] = ['all' => true];
    $bundles[SolidInvoiceSaasBundle::class] = ['all' => true];
}
```
  (add `use function in_array; use function sprintf;` are unnecessary in the no-namespace bundles.php — global functions resolve directly.)
  - `config/services_test.php` and `src/CoreBundle/Resources/config/services/services.php`: change the `SOLIDINVOICE_PLATFORM === 'saas'` / `!== 'saas'` env reads to the same `SOLIDINVOICE_MODE` read.
  - `src/Kernel.php`: verify whether it reads `SOLIDINVOICE_PLATFORM` directly or keys off bundle presence; update only if it reads the env var.

- [ ] **Step 5: Run tests + `bin/console cache:clear --env=test` + `bin/console debug:container --env=test` smoke check** to confirm the container still compiles in default (self-hosted) mode.

- [ ] **Step 6: Quality gate + commit** — `feat(core): switch application mode switch to SOLIDINVOICE_MODE and retire SOLIDINVOICE_PLATFORM/SOLIDINVOICE_DEMO`

---

### Task R3: Redefine `saas_enabled` toggler + migrate all runtime SaaS consumers to `ModeResolver` (A2)

**Files (migrate each `isActive('saas_enabled')` / `toggle('saas_enabled')` runtime consumer to the resolver):**
- Modify: `config/packages/toggler.php` — redefine `'saas_enabled' => '@=env("SOLIDINVOICE_MODE") === \'saas\''`; **delete** the `demo_enabled` flag (added by committed Task 2 — no longer used).
- Modify (PHP consumers → inject `ModeResolver`, replace `$toggle->isActive('saas_enabled')` with `$modeResolver->isSaas()`):
  - `src/ClientBundle/Validator/Constraints/WithinPlanClientLimitValidator.php:52`
  - `src/InvoiceBundle/Validator/Constraints/WithinPlanInvoiceLimitValidator.php:54`
  - `src/UserBundle/Security/VerifiedUserChecker.php:46`
  - `src/McpBundle/Security/Voter/McpAccessVoter.php:46`
  - `src/ApiBundle/Security/Voter/ApiAccessVoter.php:46`
  - `src/CoreBundle/Action/CreateCompany.php:76`
  - `src/SaasBundle/Service/SubscriptionService.php:38` (uses property name `toggler`)
- Modify (Twig → `is_saas()`): `src/SettingsBundle/Resources/views/Form/fields.html.twig` (4 sites), `src/CoreBundle/Resources/views/Company/create.html.twig:89`.

**Interfaces:** Consumes `ModeResolver::isSaas()`. Behavior must be byte-for-byte equivalent to the prior `saas_enabled` checks (since `saas_enabled` now derives from `MODE==='saas'`, and self-hosted/demo are both non-saas, existing self-hosted behavior is preserved).

- [ ] **Step 1: Read each consumer** to see whether it already injects `ToggleInterface` (rename/replace the dependency) and confirm its exact property name (`toggle` vs `toggler`).
- [ ] **Step 2: For each PHP consumer, add/adjust a focused test** (or extend the existing one) asserting the saas-vs-non-saas branch still behaves correctly when constructed with a `ModeResolver` in saas vs self-hosted mode. Keep changes minimal and mechanical.
- [ ] **Step 3: Migrate the toggler config + all consumers.** Keep the `toggle('saas_enabled')`-style behavior available via the redefined flag for any site not worth converting — BUT per decision A2, convert the listed runtime consumers to `ModeResolver`/`is_saas()`. Leave the `saas_enabled` toggler defined (redefined) so any transitively-dependent vendor/platform code still resolves.
- [ ] **Step 4: Add an `is_saas()` Twig function** (in the Task R4 extension — coordinate ordering: if R4 lands first, use it; otherwise add `is_saas()` here and R4 subsumes it). Simplest: do R4 before R3's Twig edits, or include the `is_saas()` function addition in whichever task runs first. Note this ordering in the commit.
- [ ] **Step 5: Run** the affected bundles' suites (`bin/phpunit src/ClientBundle src/InvoiceBundle src/UserBundle src/McpBundle src/ApiBundle src/CoreBundle src/SaasBundle`) as regression.
- [ ] **Step 6: Quality gate + commit** — `refactor(core): derive saas_enabled from SOLIDINVOICE_MODE and migrate SaaS checks to ModeResolver`

---

### Task R4: Replace the demo Twig extension with mode-aware Twig functions; delete `DemoMode`/`DemoExtension`

**Files:**
- Create/Replace: `src/CoreBundle/Twig/Extension/ModeExtension.php` (or rename `DemoExtension.php`) exposing `app_mode()`, `is_demo()`, `is_saas()`, `demo_username()`, `demo_password()`, `demo_signup_url()` — all delegating to `ModeResolver`.
- Delete: `src/CoreBundle/Demo/DemoMode.php` and `src/CoreBundle/Twig/Extension/DemoExtension.php` (committed Tasks 3/4) once all consumers are migrated (Phase M does the consumer swaps — sequence R4 to define the replacement, then Phase M migrates callers, then delete `DemoMode` in the last migration task or here if nothing references it yet).
- Test: replace `DemoExtensionTest.php` + `DemoTwigFunctionsTest.php` with `ModeExtensionTest.php` + a functional `ModeTwigFunctionsTest.php` asserting defaults (self-hosted → `app_mode()==='self-hosted'`, `is_demo()` false, demo accessors empty).

**Interfaces:** Produces the Twig contract above. Consumes `ModeResolver`.

- [ ] **Step 1–5:** Mirror the committed Task 4 structure (unit test of `getFunctions()` names + delegation using a real `ModeResolver`; functional test rendering each function via the real container in default mode). Implementation delegates each function to the resolver. Register is automatic (autoconfigure). Commit — `refactor(core): expose mode-aware Twig functions, remove demo_enabled`.

> **Note on `DemoMode` deletion:** `DemoMode` is consumed by committed Tasks 6/7 (reset command), 8 (register/oauth), 9 (mailer), 10 (notifications) and the partial 11 (payments). Delete `DemoMode.php` only after Phase M + Task F1 have migrated every consumer. The last of those tasks removes the file and confirms `grep -rn "DemoMode" src` returns nothing.

---

## Phase M — Migrate committed demo guards to capabilities

Each task: inject `ModeResolver` in place of `DemoMode`, swap the check, update the test to construct a real `ModeResolver('demo', …)` instead of the `DemoMode`+`ToggleInterface` mock, re-run the bundle suite, commit.

### Task M1: Reset command → `ModeResolver` (revises committed Task 7)
- `src/CoreBundle/Command/DemoResetCommand.php`: replace `DemoMode $demoMode` with `ModeResolver $modeResolver`; `isEnabled()` → `return $this->modeResolver->isDemo();`; the runtime guard's first statement → `if (! $this->modeResolver->isDemo()) { … return self::FAILURE; }`; `demoMode->username()/password()` → `modeResolver->demoUsername()/demoPassword()`.
- `src/CoreBundle/Tests/Command/DemoResetCommandTest.php`: construct `new ModeResolver('demo', 'demo@example.com', 'demo-password')` for the enabled cases and `new ModeResolver()` (self-hosted) for the disabled case; preserve the falsifiable-`dropDatabase()` happy-path test and the lock-skip/guard tests.
- Commit — `refactor(demo): drive demo reset command from ModeResolver`.

### Task M2: Registration + OAuth → `Capability::UserRegistration` (revises committed Task 8)
- `src/UserBundle/Action/Register.php`: `demoMode->isEnabled()` → `! $this->modeResolver->allows(Capability::UserRegistration)`. Keep the invitation-bypass and the `allow_registration` toggle OR-logic intact: `if (! $request->query->has('invitation') && (! $this->modeResolver->allows(Capability::UserRegistration) || ! $this->toggle->isActive('allow_registration')))`.
- `src/UserBundle/Security/OAuth/OAuthAuthenticator.php`: same swap in the new-user branch.
- Update both tests to construct a real `ModeResolver('demo', …)`. Preserve the existing-OAuth-user-still-logs-in test.
- Commit — `refactor(demo): gate registration on Capability::UserRegistration`.

### Task M3: Mailer → `Capability::RealEmailDelivery` (revises committed Task 9)
- `src/MailerBundle/Factory/MailerConfigFactory.php`: guard becomes `if (! $this->modeResolver->allows(Capability::RealEmailDelivery)) { return $this->inner->fromStrings($dsns); }` as the first statement of `fromStrings()`.
- Update `MailerConfigFactoryTest.php` to the real-`ModeResolver` construction; keep the `SystemConfig::get()`-never-called assertion and the `Transports::default` null-transport unwrap.
- Commit — `refactor(demo): gate real email delivery on Capability::RealEmailDelivery`.

### Task M4: Notifications → `Capability::RealNotificationDelivery` (revises committed Task 10)
- `src/NotificationBundle/Notification/NotificationManager.php`: last constructor arg becomes `ModeResolver`; first statement of `sendNotification()` → `if (! $this->modeResolver->allows(Capability::RealNotificationDelivery)) { return; }`.
- Update `src/NotificationBundle/Tests/NotificationManagerTest.php` to real-`ModeResolver`; keep the `shouldNotReceive('send')` assertions.
- Commit — `refactor(demo): gate real notification delivery on Capability::RealNotificationDelivery`.

---

## Phase F — Finish remaining feature work (against the new contract)

### Task F1: Block online payment capture → `Capability::OnlinePaymentCapture` (completes partial Task 11)
- `src/PaymentBundle/Action/Prepare.php`: the guard is already applied on disk using `DemoMode`; change it to inject `ModeResolver` and use `if (! $this->modeResolver->allows(Capability::OnlinePaymentCapture)) { throw new AccessDeniedHttpException(); }` inside the `capture_online` branch (before `createCaptureToken`).
- `src/PaymentBundle/Tests/Action/PrepareTest.php`: finish the two missing test methods (online-blocked-in-demo + offline-unaffected-in-demo) using a real `ModeResolver('demo', …)` via the existing `buildAction` helper params (`formFactory`/`payum`/mode). Run the TDD mutation check (comment out the guard → online-block test fails → restore → passes). Then run `bin/phpunit src/PaymentBundle`.
- After this task, `DemoMode` should have no remaining consumers — delete `src/CoreBundle/Demo/DemoMode.php` and its test, and `grep -rn "DemoMode" src` to confirm zero hits (fold the delete into this commit or a tiny follow-up).
- Commit — `feat(demo): block online payment capture via Capability::OnlinePaymentCapture`.

### Task F2: Lock shared-account email/password → `Capability::CredentialChange`
- Per the original plan Task 12, but gate on `! $modeResolver->allows(Capability::CredentialChange)` in `ProfileType`, `EditProfile`, `ChangePasswordType`, and the change-password action. Tests construct a real `ModeResolver('demo', …)`.
- Commit — `feat(demo): lock shared-account credentials via Capability::CredentialChange`.

### Task F3: Demo warning alert on sensitive config forms
- Per original plan Task 13, but the Twig guard is `{% if is_demo() %}` (not `demo_enabled()`). Partial `@SolidInvoiceCore/Demo/_warning_alert.html.twig` + includes in the SMTP / notification-integration / payment-gateway forms.
- Commit — `feat(demo): add demo warning alert to sensitive config forms`.

### Task F4: Force DEMO watermark on invoice & quote PDFs
- Per original plan Task 14, Twig guard `{% if is_demo() %}` emitting `<watermarktext content="DEMO" .../>` unconditionally. Tests inject a real `ModeResolver('demo', …)` into the container (`self::getContainer()->set(ModeResolver::class, new ModeResolver('demo','u','p'))`).
- Commit — `feat(demo): force DEMO watermark on invoice and quote PDFs`.

### Task F5: DEMO overlay on printer-friendly HTML views
- Per original plan Task 15, `{% if is_demo() %}` CSS overlay in the 4 view templates.
- Commit — `feat(demo): add DEMO overlay to printer-friendly views`.

### Task F6: Login banner + credential prefill
- Per original plan Task 16, but `Login.php` injects `ModeResolver` and seeds `last_username` from `modeResolver->demoUsername()` when `isDemo()`; template uses `is_demo()` + `demo_username()`/`demo_password()`.
- Commit — `feat(demo): show demo credentials banner and prefill login form`.

### Task F7: In-app demo banner + signup CTA
- Per original plan Task 17, `Layout/default.html.twig` `{% block demo_banner %}` guarded by `is_demo()`, CTA shown when `demo_signup_url()` non-empty.
- Commit — `feat(demo): add in-app demo banner with signup CTA`.

### Task F8: Full-suite verification
- `bin/ecs check --fix && bin/phpstan analyse && bin/phpunit` across all affected bundles; confirm no `DemoMode`/`SOLIDINVOICE_DEMO`/`SOLIDINVOICE_PLATFORM`/`demo_enabled` references remain (`grep -rn` each → zero in `src`/`config`, allowing only historical mentions in comments if intentional); confirm ViewTest snapshots unchanged (default self-hosted mode). Address the carried-over Minor findings from the v1 review loops (OAuth toggle-assertion comment clarity; mailer `Transports` reflection fragility) if cheap.

---

## Self-Review (controller, after writing)

- **Coverage:** every restriction in spec §3.4 maps to a Capability + a Phase M/F task; every SaaS touch-point from the migration map (R3) is listed; mutual exclusion is structural (R1/R2). ✔
- **Contract consistency:** `ModeResolver`/`ApplicationMode`/`Capability`/Twig names are identical across R, M, F. ✔
- **Ordering:** R1 → R2 → R4 (Twig, needed by R3's template edits and Phase F) → R3 → M1–M4 → F1 (deletes `DemoMode`) → F2–F8. R4 before R3 so `is_saas()` exists for the template migration. ✔
- **Placeholders:** the only "read the file first" directives are for files whose current line numbers shifted under committed tasks — each names the exact symbol/edit to make. ✔
