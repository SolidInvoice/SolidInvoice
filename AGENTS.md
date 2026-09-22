# AGENTS.md — SolidInvoice

Guidance for AI agents working in this repository. Everything here is derived from the
repository itself; each rule cites the file that establishes it. Where this file and the
code disagree, the code wins — fix this file in the same PR.

**Default branch is `3.1.x`, not `main`.** Branch from and target `3.1.x`. Release
branches are one per minor (`2.3.x`, `2.4.x`, `3.0.x`, `3.1.x`); `main` does not exist.
`unit-tests.yml` only runs on pushes to `3.0.x` and `3.1.x`.

---

## 1. What this is

Open-source invoicing/billing app. PHP, Symfony, bundle-per-feature, built on top of
`solidworx/platform` (required as `dev-main` — `composer.json`).

Versions below are the constraints in `composer.json` / `package.json` as of this writing.
**Read those files rather than trusting this table** — it has drifted before.

| Thing | Constraint | Source |
|---|---|---|
| PHP | `>=8.4.1`, `config.platform.php = 8.4.1` | `composer.json` |
| Symfony | `^8.1` | `composer.json`, `extra.symfony.require` |
| Doctrine ORM / DBAL | `^3.6` / `^4.4` | `composer.json` |
| API Platform | `^4.3` | `composer.json` |
| PHPUnit | `^13.2` | `composer.json`, `phpunit.xml.dist` schema |
| PHPStan | `^2.2`, **level 6** | `phpstan.neon` |
| Tabler | `@tabler/core ^1.4.0` | `package.json` |
| Bun | `bun@1.3.14` (`packageManager`) | `package.json` |
| Node | `22` (`engines`) | `package.json` |

CI tests PHP **8.4 and 8.5** (`unit-tests.yml` matrix); coverage runs on 8.5.
`phpstan.neon` sets `phpVersion: 80500`.

---

## 2. Commands — the real ones

These are copied from `.github/workflows/` and `captainhook.json`. Binaries live in `bin/`
because `composer.json` sets `config.bin-dir: bin`.

### Tests

```bash
bin/paratest                        # the full suite — this is what CI runs
bin/paratest --coverage-clover build/logs/clover.xml   # with coverage (CI, PHP 8.5)
bin/phpunit --group=installation    # installation tests, separately (see below)
bin/paratest --exclude-group=installation   # db-tests.yml, non-SQLite databases
```

`bin/phpunit` still works for a single file or filter, and is what you want for a fast
loop:

```bash
bin/phpunit src/InvoiceBundle/Tests/...Test.php
bin/phpunit --filter testSomething
```

**Do not run `bin/paratest --group=installation`.** The installation tests are excluded by
default in `phpunit.xml.dist`, and the comment there explains exactly why: they drive a
real browser through a Panther-managed web server that has its own process, its own
database connection and its own view of `SOLIDINVOICE_CONFIG_DIR`
(`var/cache/test/config`). They cannot see data inside `dama/doctrine-test-bundle`'s
uncommitted transaction, their writes are never rolled back, and their `setUp()` deletes a
directory every paratest worker shares. `unit-tests.yml` runs them last, with `bin/phpunit`
and one browser, deliberately.

### Test database

You do not need to provision one. `phpunit.xml.dist` sets

```
SOLIDINVOICE_DATABASE_URL=sqlite:///%kernel.cache_dir%/solidinvoice_%app_mode%_%env(default:app_mode:TEST_TOKEN)%.db
```

so the default suite is **SQLite, created per app-mode and per paratest worker token**.
`db-tests.yml` overrides that URL to run the same suite against MySQL 5.7/8.0/8.3/8.4/9,
MariaDB 10.4–11.4 and PostgreSQL 16/17. If your change touches SQL, schema or a Doctrine
filter, assume `db-tests.yml` is the job that will catch you.

**A green local run does not mean the column fits.** SQLite ignores `VARCHAR(n)` — it
stores whatever you give it — so a value that overflows a bounded column passes every
test here and fails on MySQL in `db-tests.yml`, or in production. Lengths are not
test-enforced on the default suite. If you add or widen a bounded column, trace every
writer of it yourself rather than trusting the suite.

### Static analysis and style

```bash
bin/ecs check                       # CS check (cs.yml)
bin/ecs check --fix                 # autofix
bin/phpstan analyse -c phpstan.test.neon   # what static-analysis.yml actually runs
bin/rector process --dry-run
bin/rector process
composer normalize --no-update-lock --diff --dry-run   # cs.yml
bun run lint:js                     # eslint assets
bun run lint:css                    # stylelint 'assets/**/*.{scss,css}'
```

Two PHPStan configs exist and they are not interchangeable. `phpstan.neon` is level 6
against `src` + `migrations` using the **dev** container XML. `phpstan.test.neon` includes
it plus `phpstan-test-baseline.neon` and points at the **test** container XML. CI runs the
test one, and warms the cache first:

```bash
bin/console cache:warmup -n -vvv -e test
bin/phpstan analyse -c phpstan.test.neon
```

Running bare `bin/phpstan analyse` will disagree with CI.

### Frontend

```bash
bun install                 # bun install --frozen-lockfile in CI
bun run dev                 # encore dev
bun run watch               # encore dev --watch
bun run build               # encore production --progress
```

`bun run build` is a **prerequisite for PHPStan and for the test suite in CI** —
`static-analysis.yml`, `unit-tests.yml` and `db-tests.yml` all run `bun install && bun run
build` before PHP runs.

### Pre-commit

`captainhook.json` enables a `pre-commit` hook that runs PHP linting, `bin/ecs check`,
`bin/rector --dry-run`, and `bin/console lint:yaml` over `src/**/*.yml`. If you commit
locally, these must pass. There is also a `.pre-commit-config.yaml` (gitleaks, shellcheck,
eslint, trailing-whitespace) for the `pre-commit` framework.

---

## 3. Baselines — what is already forgiven

- `phpstan-baseline.neon` — **~1375 lines**. It is large and it is load-bearing.
- `phpstan-test-baseline.neon` — 37 lines.

Do not regenerate either baseline to make your change pass. Adding entries hides new
errors across the whole codebase. Fix the error, or if the error is genuinely
pre-existing and untouched by you, leave it in the baseline it is already in.

---

## 4. House style

### File header — mandatory, enforced

`ecs.php` configures `HeaderCommentFixer` with `location: after_declare_strict`. Every PHP
file under `config/`, `src/`, `tests/`, `migrations/`, plus `ecs.php` and `rector.php`,
starts exactly like this:

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

namespace SolidInvoice\...;
```

Note the order: `declare` **before** the header comment. `bin/ecs check --fix` will fix it;
getting it right first avoids a diff.

### ECS rules actually configured (`ecs.php`)

Sets: `PSR_12`, `SPACES`, `DOCBLOCK`, `COMMENTS`, `NAMESPACES`, `CLEAN_CODE`. On top of
those, the rules the founder has explicitly opted into are worth knowing because they
change how you write code:

- `SingleQuoteFixer` — single quotes unless you interpolate.
- `ExplicitStringVariableFixer` — `"{$foo}"`, not `"$foo"`.
- `VoidReturnFixer` — `: void` is not optional.
- `NoUselessElseFixer` — no `else` after a returning `if`.
- `OrderedImportsFixer` with `imports_order: ['const', 'class', 'function']`.
- `SingleClassElementPerStatementFixer` for `const` and `property`.
- `ClassDefinitionFixer` with `single_line: true`.

Explicitly **skipped**: `MethodChainingNewlineFixer`, `PhpdocLineSpanFixer`,
`RemoveDeadVarThisFixer`, and `config/env` entirely.

### PHP conventions

- `declare(strict_types=1);` everywhere.
- Full parameter and return types.
- `final` by default. **Doctrine entities are never `final`** — proxies extend them.
- Typed class constants where the value is fixed: `private const string INDEX_NAME = '…';`
  (`migrations/Version30100_1.php`), `private const int DEFAULT_SUBUNIT = 2;`
  (`src/MoneyBundle/Currency/CurrencyScale.php`).
- **Backed enums, never class constants, for fixed sets of values.** The codebase is
  consistent on this: `src/TaxBundle/Enum/{TaxType,TaxDirection,TaxCategory,RoundingStrategy}.php`,
  `src/UserBundle/Enum/UserSettingType.php`, `src/AppMode.php`. Enums carry behaviour —
  `RoundingStrategy::toRoundingMode()` and `::getLabel()` are methods on the enum, not a
  `match` at the call site.
- `readonly` classes where the object is a value: `final readonly class CurrencyScale`.

### Layout

```
src/<Name>Bundle/
├── Action/        # HTTP entry points — invokable, one per action
├── Command/
├── Doctrine/      # filters, types, hydrators
├── Entity/
├── Enum/
├── Form/
├── Listener/  or  EventSubscriber/
├── Manager/       # multi-step domain operations
├── Mcp/           # MCP tool classes, e.g. TaxReadTools / TaxWriteTools
├── Repository/
├── Resources/config/{routing,services}/   # services wired in PHP, not YAML
├── Twig/{Extension,Components}/
├── Validator/Constraints/
└── Tests/         # Functional/ for functional; unit tests sit at the top level
```

Namespace is `SolidInvoice\<Name>Bundle\…` (`composer.json` psr-4 `SolidInvoice\ → src/`).
Unit tests live directly under `Tests/`, **not** under `Tests/Unit/`.

### Actions, not controllers

HTTP entry points are single-purpose invokable classes in `Action/`:
`src/TaxBundle/Action/{Add,Edit,Index,Validate}.php`,
`src/CoreBundle/Action/{CreateCompany,DeleteCompany,SelectCompany}.php`. Name them for
what they do. Business logic belongs in a `Manager/`, a service, or the entity — not in
the Action.

### API Platform resources

Resources are configured with attributes **on the entity**, not in YAML/XML. Operations
are declared explicitly in `operations:`; there is no reliance on the default set.
Serialization is group-based (`normalizationContext` / `denormalizationContext`, e.g.
`contact_api`, `invoice_api`) — a field is exposed by putting it in a group, never by
leaving it ungrouped.

**A sub-resource `Post` needs `read: false`.** This is the one that bites, and six
entities carry it:

```php
new Post(
    uriTemplate: '/invoices/{invoiceId}/lines',
    uriVariables: [
        'invoiceId' => new Link(fromProperty: 'lines', fromClass: Invoice::class),
    ],
    // The `lines` link would otherwise have API Platform deserialize into the owner's
    // existing line, so a second post overwrites the first. A create has nothing to read.
    read: false,
    processor: InvoiceLinePersistProcessor::class,
),
```

Without it the `Link` makes API Platform *read* the parent's existing child and
denormalize onto it, so posting a second line silently overwrites the first instead of
creating one. The endpoints that get this right:
`src/InvoiceBundle/Entity/Line.php`, `src/InvoiceBundle/Entity/RecurringInvoiceLine.php`,
`src/QuoteBundle/Entity/Line.php`, `src/ClientBundle/Entity/{Address,Contact}.php`,
`src/CoreBundle/Entity/CustomField/CustomField.php`. Copy the pattern when you add another.

Writes that are more than a persist go through a `ProcessorInterface`, not the entity —
`InvoiceLinePersistProcessor` above is the shape. MCP tools live alongside in
`src/*Bundle/Mcp/` (26 of them) and are a **separate** entry point to the same domain:
fixing a rule in a processor does not fix it for the MCP tool, so check both.

### Services

Wired in PHP: `config/services.php`, `config/services_test.php`, and per-bundle
`src/*/Resources/config/services/*.php`. Constructor injection only. Rector actively
enforces this (`SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION` in `rector.php`).

Config is read from `platform.yaml` (see §6) and `config/packages/`. `config/reference.php`
is **auto-generated** — `rector.php` skips `DeclareStrictTypesRector` on it and `ecs.php`
skips the header and `TypeToVarTagFixer` on it. Do not hand-edit it.

---

## 5. Rector — it will rewrite your code

`rector.php` runs a very wide set (`UP_TO_PHP_84`, code quality, coding style,
privatization, Doctrine, PHPUnit, Symfony, Carbon, Foundry, and
`SolidWorxSetList::PLATFORM` from the Platform package).

**The `rector` job in `static-analysis.yml` auto-commits back to your branch.** It runs
`bin/rector --ansi` then `bin/ecs --fix --ansi` and pushes a `[rector] Rector fixes`
commit via `git-auto-commit-action`. This only runs when the PR head is in
`SolidInvoice/SolidInvoice` — not for forks. Expect it, and pull before you push again.

The `withSkip()` list in `rector.php` is a list of hard-won exceptions, each with a comment
explaining why. Treat them as "do not change this":

- `src/Kernel.php` — `RenameClassRector` and
  `MakeInheritedMethodVisibilitySameAsParentRector` are skipped. The `instanceof` check
  must target the **legacy HttpKernel** `BundleInterface` (renaming it to the DI one
  inverts the logic), and `configureContainer()` must stay `protected`.
- `src/InvoiceBundle/Entity/Invoice.php` — `RemoveNewArrayCollectionOutsideConstructorRector`
  skipped because `__clone()` must build a new collection.
- `src/PaymentBundle/Resources/config/services/services.php` — those services must stay
  `public()`.
- `config/env` — generated secrets vault, rewritten at runtime by the installer.

---

## 6. Platform is load-bearing

`solidworx/platform` is required as `dev-main`. It is developed alongside SolidInvoice, so
"update platform" commits are routine in this history. Do not work around Platform — if
something is missing, say so and let it be added upstream.

Rules that hold throughout the codebase:

- Repositories extend `SolidWorx\Platform\PlatformBundle\Repository\EntityRepository`.
- Console commands extend `SolidWorx\Platform\PlatformBundle\Console\Command`.
- **The webpack config is Platform's.** `webpack.config.js` is
  `import Encore from '@solidworx/platform/webpack.config.js'` and then chains entries onto
  it. `package.json` maps `@solidworx/platform` to
  `file:vendor/solidworx/platform/assets`. You configure entries; you do not reconfigure
  Encore.
- **That `file:` dependency is copied into `node_modules`, not symlinked.** Editing
  anything under `vendor/solidworx/platform/assets` therefore changes nothing until you
  re-run `bun install`. The loop when you are working on both repos at once is: edit
  Platform's assets → `bun install` → `bun run dev` → hard-reload the browser. Skipping the
  `bun install` is the usual reason a Platform change "does not apply".
- UI comes from Platform's `UiBundle` as Twig components: `<twig:Ui:Card>`,
  `<twig:Ui:Alert>`, `<twig:Ui:Modal>`. Props and blocks are documented in
  `vendor/solidworx/platform/src/Bundle/Ui/Docs/{Card,Alert,Modal}.md`. Read those before
  hand-rolling markup. If a component cannot do what you need, build the minimum custom
  HTML and flag the gap for Platform rather than forking the component.
- App-level configuration is `platform.yaml` at the repo root, validated against
  `platform-schema.json` in the Platform package. It carries `platform.models.user`,
  `platform.doctrine.types.enable_utc_date`, the `saas:` block and the `ui:` block.

---

## 7. Frontend

- There is **one** JS entry point: `assets/app.ts`. `webpack.config.js` has a single
  `addEntry`; `assets/webmcp.ts` is imported *by* `app.ts` and is not an entry of its own.
  There is no `assets/core.ts` in this repo — that is Platform's.
- Style-only entries: `login`, `register`, `installation`, `pdf`, `email-colors`,
  `email-modern`.
- Stimulus via `enableStimulusBridge('./assets/controllers.json')`.
- Controllers live in `assets/controllers/` and are **TypeScript (`.ts`) here**. Note this
  is the opposite of Platform, where controllers must be `.js` — see the Platform
  AGENTS.md. SolidInvoice's controllers are not distributed via npm, so TS is fine.
- Controller filenames are currently **inconsistent** — both
  `vat-validator-controller.ts` and `global-search_controller.ts` exist. Match the
  neighbouring files in whatever area you are editing rather than "fixing" the other style.
- SCSS in `assets/scss/`, with design tokens under `assets/scss/design-system/_tokens.scss`
  using a `--swp-` custom-property prefix.
- Tabler/Bootstrap supplies button, table, modal and alert styling. Do not write SCSS that
  duplicates it.
- **A page rendered outside the app shell must also load `_platform_ui`.** `app.css`
  carries this app's styles only — the Bootstrap/Tabler core lives in Platform's entry. A
  standalone template (a preview, a print view, anything not extending the main layout)
  needs both:

  ```twig
  {{ encore_entry_link_tags('_platform_ui') }}
  {{ encore_entry_link_tags('app') }}
  ```

  `src/SaasBundle/Resources/views/Settings/template_preview.html.twig` is the one page in
  the repo that does this. Without it the page renders unstyled and it looks like a build
  problem.
- Icons via `ux_icon('tabler:…')`; local icon set in `assets/icons/tabler`.

Server-rendered Twig is the default. Interactivity is Stimulus controllers and Symfony UX
Live Components (`symfony/ux-live-component`, and `Twig/Components/` directories in
`src/UserBundle`, `src/TaxBundle`, `src/NotificationBundle`). There is no SPA.

---

## 8. Translations — no hard-coded strings

`TRANSLATING.md` is a real spec and it is enforced socially, not by a linter. The rules
that bite:

- **No hard-coded user-facing English**, anywhere: templates, forms, controllers,
  validators, enums.
- Catalogs are app-level, one file per domain+locale: `translations/<domain>.<locale>.yml`.
  They are **not** split per bundle.
- Domains are a fixed small set: `messages` (default), `email`, `validators`, plus
  `messages+intl-icu` only where ICU plural/select is needed.
- Keys are dotted, lowercase, feature-namespaced (`invoice.list.title`), stored **nested**
  and alphabetically sorted. English is the source locale.
- **Never use natural-language text as a key.** `'Save'|trans` is wrong — use `action.save`.
- Reuse the shared CoreBundle keys instead of redefining: `action.*`, `label.*`,
  `billing.*`, `status.*`.
- No leaf/namespace clashes — a key cannot be both a value and a parent. Never introduce a
  bare top-level word key.

---

## 9. Database, migrations and multi-tenancy

### IDs

ULIDs, not UUIDs, despite older docs saying otherwise:

```php
#[ORM\Column(name: 'id', type: UlidType::NAME)]
#[ORM\Id]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UlidGenerator::class)]
private Ulid $id;
```

### Migrations — `migrations/`, `DoctrineMigrations` namespace

Naming is **not** Doctrine's default. One file per version, split into numbered parts:
`Version{major}{minor}{patch}_{part}.php` — 3.1.0 is currently spread over several
`Version30100_*.php` parts. Pre-2.4 files have no part suffix (`Version20317.php`). Check
`migrations/` for the current highest part rather than assuming; part numbers are not
guaranteed contiguous.

Migrations are `final`, live in namespace `DoctrineMigrations`, and are classmapped by
`composer.json` `autoload-dev`.

Written against the **Schema tool, not raw SQL**, and they must be idempotent and
portable. Copy the `up()`/`down()` shape from `Version30100_1.php` and the
`isTransactional()` guard from `Version30100_3.php` — `Version30100_1.php` still has the
stale `MySQLPlatform` form the note below warns about:

```php
public function isTransactional(): bool
{
    // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
    // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
    return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
}

public function up(Schema $schema): void
{
    $table = $schema->getTable('invoices');

    if (! $table->hasIndex(self::INDEX_NAME)) {
        $table->addIndex(['due', 'status'], self::INDEX_NAME);
    }
}
```

**`AbstractMySQLPlatform`, never `MySQLPlatform`, in any platform check.** `MariaDBPlatform`
is a *sibling* of `MySQLPlatform`, not a subclass, so a bare `instanceof MySQLPlatform` is
false on MariaDB — and `db-tests.yml` runs MariaDB 10.4 through 11.4. Only
`Version30100_3.php` onwards get this right; the 29 older migrations still carry the bare
form and are not a pattern to copy. The same applies to `preUp()`/`postUp()` guards, and to
`FOREIGN_KEY_CHECKS` toggles.

`down()` is implemented, guarded the same way. `getDescription()` is filled in. Comments
explain only the non-obvious part (that one explains *why* `company_id` must not lead the
index) — there is a commit in this history titled "Trim the migration's comments to the
non-obvious parts".

Remember `db-tests.yml`: your migration runs on MySQL 5.7 through 9, MariaDB 10.4 through
11.4, and PostgreSQL 16/17.

### Doctrine filters — `src/CoreBundle/Doctrine/Filter/`

Two global filters:

- `CompanyFilter` — multi-tenancy, filters by `company_id`.
- `ArchivableFilter` — soft deletes.

`CompanyFilter` is the most performance-sensitive file in the repo and it is commented
accordingly. Read it before touching anything tenant-scoped:

- The company id is bound as an **uppercase hex string**, not raw binary, because quoting
  binary into a filter parameter is not portable (see `CompanySelector::switchCompany()`).
- It decodes the *literal* rather than encoding the *column* wherever the platform allows:
  PostgreSQL compares a native `uuid`; MySQL wraps the literal in `UNHEX()`, which folds to
  a constant. Only SQLite (< 3.41, no hex decoder) falls back to `HEX(column)`.
- **Wrapping the column in `HEX()` makes the comparison non-sargable**, disqualifies every
  index on `company_id`, and forces a full table scan on every multi-tenant query. This is
  the trap the file exists to avoid.
- `User` is a special case — it filters through the `user_company` join table, not a direct
  `company` association.

Entities opt in via the `CompanyAware` trait (`src/CoreBundle/Traits/Entity/CompanyAware.php`).
Other cross-cutting traits: `Archivable`, `TimeStampable`.

### Line ordering — `LinePosition` / `LinePositions`

Invoice, quote and recurring-invoice lines are ordered by an integer `position` the
entities maintain themselves: `LinePositions` on the owner (`Invoice`, `Quote`,
`RecurringInvoice`) and `LinePosition` on the line. The owner's `addLine()` places; the
line's `#[ORM\PrePersist]` handles a line attached directly, and `compactLinePositions()`
closes gaps after a removal.

**Do not reach for Gedmo Sortable here.** It binds the sortable group untyped, so on a
ULID primary key `getMaxPosition()` reads `-1` and reordering silently corrupts every
position. That is why this bookkeeping is hand-rolled.

`LineInterface::UNPLACED` is `PHP_INT_MAX` and is a sentinel, not a stored value — the
column is an `INTEGER` and the `PrePersist` hook exists to make sure it never lands in the
database.

### Hosted tier vs self-hosted

`src/AppMode.php` is a backed enum with `SAAS` and `SELF_HOSTED`. It gates bundle loading
and service wiring in `src/Kernel.php` and
`src/CoreBundle/Resources/config/services/services.php`. `SaasBundle` and Platform's
`SaasBundle` only apply in `saas` mode. The app-mode is also part of the test database name
(`solidinvoice_%app_mode%_…`), so both modes get their own database. If you add something
hosted-only, gate it on `AppMode::SAAS` — do not assume one mode.

---

## 10. Money and tax

**Money is minor units in an integer column, and the factor is not always 100.**

`src/MoneyBundle/Currency/CurrencyScale.php` exists precisely because that assumption
broke. Its own docblock:

> The factor follows the currency's own decimal count, not a fixed 100 — assuming 100
> everywhere is what put JPY and BHD amounts out by two and one orders of magnitude
> respectively.

So:

- Never hard-code `* 100` or `/ 100`. Use `CurrencyScale::factorFor()`,
  `::subunitFor()`, `::toMinorUnit()`.
- `toMinorUnit()` **does not round** — a value with more precision than the currency allows
  keeps its fraction. If you are building a `Money` directly rather than persisting through
  the integer column, you must round.
- `toMajorUnit()` returns a `float` only because its two callers feed a display layer (a
  form view and a chart dataset). Its docblock says: *do not reuse this where the exact
  value matters* — go through `toMinorUnit()` and keep the `BigDecimal`.
- Arithmetic goes through `brick/math` (`BigDecimal`/`BigNumber`) and `moneyphp/money`.
  Never floats.
- Currency comes from the `Client`. There is no default currency to fall back on.
- Formatting goes through `MoneyFormatter` / `MoneyFormatterExtension`, not `number_format`.

Tax:

- `RoundingStrategy` is a configurable enum (`HalfEven` default-ish — it is listed first
  and labelled "Banker's Rounding"), mapping to `Brick\Math\RoundingMode`. Rounding is a
  **setting**, not a constant. Do not bake a rounding mode into a calculation.
- `TaxType`, `TaxDirection`, `TaxCategory` are enums — inclusive vs exclusive tax is a real
  distinction in this domain, not a display concern.
- Taxes are **snapshotted when a document is issued**:
  `src/TaxBundle/Listener/SnapshotTaxesOnIssueListener.php` and
  `src/TaxBundle/Service/TaxSnapshotCopier.php`. Changing a tax rate must not retroactively
  change an issued invoice. If you touch tax, check you have not broken the snapshot.
- Tax validators encode cross-entity rules:
  `SameCompanyAsClient`, `IncompatibleTaxConfiguration`, `ExactlyOneLine`,
  `ExactlyOneDocument`.

---

## 11. Dates and time

`platform.yaml` sets `platform.doctrine.types.enable_utc_date: true` — Platform installs
UTC date types. **Store UTC, convert at the edge.**

- Timezone is a per-user setting: `UserSettingType::Timezone`.
- `SearchQueryParser` parses user date input explicitly in
  `new DateTimeZone('UTC')`.
- CI pins `date.timezone=Africa/Johannesburg` in `unit-tests.yml`, `db-tests.yml` and
  `static-analysis.yml`. A test that passes only under UTC will fail in CI. Do not write
  tests that depend on the machine timezone.
- `nesbot/carbon` is available and Rector's `SetList::CARBON` is enabled.

---

## 12. Generated files — do not hand-edit

- `src/NotificationBundle/Form/Type/Transport/*Type.php` — ~40 files, each headed
  `// !! This file is autogenerated. Do not edit. !!`. They are produced by
  `src/NotificationBundle/Command/GenerateTransportConfigCommand.php` from the templates in
  `src/NotificationBundle/Resources/views/dev/`. To change one, change the template or the
  command and regenerate.
- `config/reference.php` — generated; skipped by both Rector and ECS.
- `config/env` — runtime secrets vault, gitignored, rewritten by the installer.

---

## 13. Testing expectations

**There is no numeric coverage threshold, and that is deliberate — do not invent one.**
`phpunit.xml.dist` sets no minimum, there is no `codecov.yml` target, and the Codecov
upload in `unit-tests.yml` runs with `fail_ci_if_error: false`. Coverage is reported, not
gated. The bar below is the real one.

- **Bug fix:** add a failing test first, then fix. The commit history reflects this —
  fix PRs like #2845/#2846 pair a behavioural fix with a test.
- **Feature:** tests are expected. `CONTRIBUTING.md`: *"Where possible, pull requests need
  to have unit tests available, and the unit tests should not fail."*
- Unit tests sit at the top level of `src/*Bundle/Tests/`; functional tests in
  `Tests/Functional/`; API tests under `Tests/Functional/Api/`.
- Mocking is **Mockery** (`mockery/mockery`), plus `phpstan/phpstan-mockery`.
- Fixtures are **Foundry** (`zenstruck/foundry ^2.10`) with `FoundryExtension` and
  auto-reset enabled in `phpunit.xml.dist`. Also available:
  `doctrine/doctrine-fixtures-bundle`, `liip/test-fixtures-bundle`. Factories live next to
  what they build, e.g. `src/UserBundle/Test/Factory/UserFactory.php`.
- `.env.test` pins `FOUNDRY_FAKER_SEED=91847`, so faker output is **deterministic**. A test
  that passes because of a lucky random value will fail for everyone else. It also sets
  `SYMFONY_DEPRECATIONS_HELPER=999999` (deprecations do not fail the build) and
  `PANTHER_ERROR_SCREENSHOT_DIR=./var/error-screenshots`.
- Browser/E2E is **Panther** plus `zenstruck/browser`. Snapshot assertions via
  `spatie/phpunit-snapshot-assertions`.
- DB isolation is `dama/doctrine-test-bundle` — each test runs in an uncommitted
  transaction. This is why the installation tests cannot join the pool.
- **`tests/bootstrap.php` builds the schema before any test runs, and throws if it
  cannot.** It runs `doctrine:schema:update --force --complete` for *two* kernels —
  `SolidInvoice\Test\Kernel` and `SolidInvoice\Test\SaasKernel`, which have separate
  cache dirs and therefore separate databases — because Foundry's auto-reset only builds
  the one belonging to whichever test class runs first. A single unmapped or half-written
  entity makes that command fail, and the bootstrap turns that into a `RuntimeException`
  that takes down the **entire suite**, not one test. If every test suddenly dies with a
  schema error, look at your working tree before you look at the tests — an untracked,
  half-finished entity is the usual cause.
- HTML snapshot assertions (`spatie/phpunit-snapshot-assertions`) mangle UTF-8 on the way
  into the snapshot file — `m²` is stored as `m&Acirc;&sup2;`. That is the serialiser, not
  the page, and the rendered output is correct. Do not "fix" it in the template, and
  expect review bots to re-report it as a bug.
- `phpunit.xml.dist` is strict: `failOnWarning`, `failOnRisky`, `failOnPhpunitDeprecation`,
  `beStrictAboutOutputDuringTests`, `beStrictAboutChangesToGlobalState`,
  `executionOrder="random"`. A test that leaks state or echoes will fail.

### Slow and awkward

- Installation/Panther tests are the slow ones and must run alone (see §2).
- `db-tests.yml` is a 14-way matrix — it is the long pole on any schema change.
- `unit-tests.yml` pins `opcache.jit=disable` on PHP 8.4 with a comment: PHP 8.4's JIT
  segfaults on its stack-limit check when the Panther web server renders deeply nested
  pages like `/install`. PHP 8.5 is unaffected. Do not remove that ini setting.
- On failure, `unit-tests.yml` posts E2E failure screenshots to the PR via
  `scripts/e2e-failure.js`.

---

## 14. PR conventions

### Branches

Short, descriptive, usually prefixed. Real examples from merged PRs:
`fix/api-line-post-overwrites`, `fix/codeql-action-pin-mismatch`,
`docs/proxmox-community-scripts`, `migrate-legacy-array-columns-to-json`,
`platform-layout`. Agent/AI branches on this repo have used an `ai/issue-<number>-<slug>`
prefix (e.g. `ai/issue-2360-invoice-email-no-recipients`). Target `3.1.x`.

### Commit messages

**Not conventional commits, despite what older docs claim.** The house style is a plain
imperative sentence describing the effect:

```
Resolve the client through the CompanyFilter when posting a contact or address
Stop a posted line from overwriting the one already there
Do not assert a collection order the mapping does not promise
Trim the migration's comments to the non-obvious parts
```

Conventional-commit prefixes in this history come almost entirely from bots
(`build(deps):` from Dependabot) with a handful of human exceptions. Write the sentence.

### Labels matter

`.github/release.yaml` builds release notes from PR labels. Categories:
`breaking-change`; `enhancement`/`feature`; `bug`/`fix`; `security`; everything else falls
to "Other Changes". `merge-up` and `skip-changelog` exclude a PR from the notes. Label your
PR or it lands under "Other Changes".

### Changelog

`CHANGELOG.md` is hand-maintained and has an `Unreleased` section at the top. Add an entry
there for anything user-visible or behaviour-changing. Release notes on GitHub are
generated separately from labels — the two are not the same thing.

### Releases

Driven by **closing a milestone** (`automatic-release.yml` triggers on
`milestone: closed`), which tags, releases, and opens a merge-up PR. You do not tag.

### Green before review

On a PR, expect: `CS` (ECS, composer-normalize, super-linter, lint:css, lint:js),
`Static Analysis` (PHPStan on `phpstan.test.neon`, plus the auto-committing Rector job),
`Unit Tests` (PHP 8.4 + 8.5), `DB Tests` (14 databases), `Security Checker`, `CodeQL`,
`Dependency Review`, `Qodana`. All of it green.

### Never merge a pull request

Not your own, not anyone else's, on any SolidWorx repository, regardless of its size, its approval state, whether CI is green, or how obvious the change looks. This includes the merge button, `gh pr merge`, the merge API, squash, rebase, auto-merge, adding to a merge queue, and pushing merge commits to `main` or any release branch directly.

The single exception: the founder explicitly instructs you to merge a specific, named pull request, in writing, in that issue thread. A general approval of a plan is not a merge instruction. "Looks good" is not a merge instruction. An approving review on the PR is not a merge instruction. Silence is never a merge instruction.

When work is ready, you open the PR, fill in the description, link the issue, and stop. Post the PR link in the issue thread and set the issue to `in_review`. The merge is the founder's, always.

If you believe a merge is urgent — a broken build, a security fix, a release blocker — you still do not merge. You say so in the issue thread, mark it high priority, and wait.

### PR hygiene for agents

- Open every PR as a **draft** and label it `agent`, unless the issue says otherwise. Agent PRs are authored under the founder's GitHub identity, so the label is what makes them distinguishable in a PR list.
- Do not close PRs you did not open.
- Do not enable auto-merge. It is a merge, just a deferred one.

> **Repository note, not an exception to the above.** This repository has no auto-merge
> automation. It had a `.mergify.yml` with one rule, which merged any non-draft PR that
> collected one approving review. That rule disagreed with this section, because an
> approving review on your PR could start a merge that the founder did not do. The file is
> deleted. Mergify also showed no sign that it was still installed.
>
> Only the founder merges your PR. An approving review does not merge it. Keep opening
> every PR as a draft and keep labelling it `agent`, but do not treat either one as the
> control: there is no automation left for them to hold off. If auto-merge automation comes
> back to this repository, change this note in the same PR.

---

## 15. Other agent-instruction files in this repo

**This file is the source of truth. Every other instruction file points here.**

The repository used to carry three near-identical 1600-line copies of these conventions
(`AGENTS.md`, `GEMINI.md`, `.github/copilot-instructions.md`, plus a fourth at
`ai/instructions.md` byte-identical to `GEMINI.md`). They drifted, because nothing kept
them in sync: all four still declared `**Current Version:** 2.3.11` against a
`composer.json` of `3.0.1`, and the Copilot copy had silently lost the "backed enums,
never class constants" rule and still advised the opposite. Per the founder's decision of
2026-09-21 they are now **pointers, not copies**.

The full map of what lives where:

| File | Role |
|---|---|
| `AGENTS.md` | **This file. Every convention lives here.** |
| `GEMINI.md` | Pointer to this file. No content. |
| `.github/copilot-instructions.md` | Pointer to this file. No content. |
| `ai/instructions.md` | Pointer to this file. No content. |
| `CLAUDE.md` | Pointer, plus an index of the Claude skills below. |
| `mate/AGENT_INSTRUCTIONS.md` | **Tool-specific, keeps its content** — AI Mate MCP tool mappings only. States no conventions. |
| `docs/CLAUDE.md` | **Scoped, keeps its content** — Docusaurus conventions for the end-user docs site under `docs/`. |
| `.claude/skills/design-system.md` | **Tool-specific, keeps its content** — design tokens and UI patterns, at a level of detail §7 does not carry. |
| `.claude/skills/solidinvoice-feature-docs/SKILL.md` | **Tool-specific, keeps its content** — a Claude Skill for writing end-user docs. |
| `.claude/skills/code-quality.md` | Pointer. Restated §3/§4 and had gone stale. |
| `.claude/skills/testing.md` | Pointer. Restated §13 and had gone stale. |

The rule for you: **read `AGENTS.md`, and when you change a convention, change it here.**
If another instruction file contradicts this one, this one wins — and that contradiction
is a bug in the other file, so fix it in the same PR. Do not add a convention to a
tool-specific file; add it here and let the tool file link. That is how the drift above
happened, and re-introducing a copy re-introduces the problem.

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: symfony/ai-mate, symfony/ai-monolog-mate-extension, symfony/ai-symfony-mate-extension.
<!-- END AI_MATE_INSTRUCTIONS -->
