Translating SolidInvoice
========================

SolidInvoice uses a single, unified translation system built on the
[Symfony Translation](https://symfony.com/doc/current/translation.html) component.
Every user-facing string lives in a translation catalog — there are no hard-coded English
strings in templates, forms, controllers, validators, or enums. This makes the app fully
translatable and lets us push/pull translations to an external provider (Crowdin by
default, but any Symfony translation provider works).

## How translations are organised

### Catalogs

All translations live in a single, app-level directory — **one file per domain + locale**:

```
translations/<domain>.<locale>.yml      e.g. translations/messages.en.yml
```

They are **not** split per bundle. Centralising them keeps shared keys in one place (no
cross-bundle duplication), and it matches where the translation provider round-trips:
`translation:pull` writes every locale back into this same `translations/` directory.

Keys are stored **nested** (each dot in the id is a level in the tree), sorted
alphabetically at every level:

```yaml
client:
    info: 'Client Info'
label:
    invoice: Invoice
    status: Status
```

> **No leaf/namespace clashes.** A key can't be *both* a value and a parent — e.g. you may
> not have `invoice: Invoice` (a value) alongside `invoice.list.title` (a namespace),
> because YAML can't nest under a scalar. When a word is needed as a standalone label, put
> it under a namespace: a generic label goes in `label.*` (`label.invoice`,
> `label.status`), and a feature-specific one nests as `<feature>.…​.label`
> (`datagrid.search.label`). Never introduce a bare top-level word key.

English (`en`) is the **source/reference** locale; every other locale falls back to it.

### Domains

There is a small, fixed set of domains — pick the one that fits:

| Domain       | Use for                                                        |
|--------------|----------------------------------------------------------------|
| `messages`   | All in-app UI strings (the default domain)                     |
| `email`      | Email and notification subject/body content                    |
| `validators` | Validation constraint messages (`#[Assert\...]`, Constraints)  |

(`messages+intl-icu` is used only where ICU plural/select syntax is needed.)

### Plurals and placeholders

Counts use [ICU MessageFormat](https://symfony.com/doc/current/reference/formats/message_format.html)
in `translations/messages+intl-icu.en.yml`. Code still uses the `messages` domain — Symfony
looks the key up in the ICU catalog first:

```yaml
# translations/messages+intl-icu.en.yml
feature:
    trial_start: '{count, plural, one {Try {plan} free for # day} other {Try {plan} free for # days}}'
```

```twig
{{ 'feature.trial_start'|trans({'%count%': days, '%plan%': plan.name}) }}
```

`%count%`-style parameter names work for ICU messages too (the `%` is stripped). The legacy
`{1} …|]1,Inf[ …` pluralisation syntax is not allowed.

Everywhere else, placeholders are `%name%`. Translate **whole sentences**: never build a
sentence by concatenating translated fragments, and never translate a fragment that is spliced
into another message — word order differs between languages. Keep HTML out of messages; wrap
the translated text in markup in the template instead. If a message must carry markup and is
printed with `|raw`, escape every parameter first (`value|e`).

### Keys

- Keys are **dotted, lowercase, and namespaced by feature**: `invoice.list.title`,
  `client.menu.add`, `custom_field.flash.created`.
- **Never use natural-language text as a key.** `'Save'|trans` is wrong — use a key.
- **Reuse** the shared keys in `CoreBundle` instead of redefining common strings:
  - `action.*` — `save`, `cancel`, `delete`, `edit`, `close`, `add`, `create`, `update`,
    `confirm`, `submit`, `back`
  - `label.*` — `required`, `optional`, `options`, `yes`, `no`
  - `billing.*` — `subtotal`, `tax`, `discount`, `total`, `withholding`, `notes_help`, `due_date`
  - `status.*` — invoice/quote/payment/client status badge labels (keyed by enum value)

Every key lives in the one shared catalog per domain, so a shared key (e.g. `action.save`)
is defined once and available everywhere.

## Using translations in code

**Twig** — use the `trans` filter with named parameters and the right domain; the filter
also handles escaping:

```twig
{{ 'invoice.list.title'|trans }}
{{ 'quote.title'|trans({'%id%': quote.quoteId}, 'email') }}
<button aria-label="{{ 'action.save'|trans }}">…</button>
```

**Forms** — pass keys as option values; the form theme translates them:

```php
$builder->add('discount', DiscountType::class, ['label' => 'billing.discount']);
```

**Flash messages** — pass a key; `flash.html.twig` runs it through `trans`:

```php
$this->addFlash('success', 'custom_field.flash.created');
```

**Validation** — put the key on the constraint; it resolves in the `validators` domain:

```php
#[Assert\Count(min: 1, minMessage: 'invoice.lines.min')]
```

> Tip: if you convert a form `label` to a key and it renders the raw key, the render path
> isn't translating it — fix the template/form-theme block (add `|trans`), don't change
> the key back.

**Grids** — column, action and filter labels are keys; `DataGrid.html.twig` translates them:

```php
RelativeDateColumn::new('due')->label('billing.due_date');
BatchAction::new('action.archive');
```

**Enums** — a label method returns a key, never English (`InvoiceStatus::getLabel()` →
`status.pending`). Enums rendered by code that expects an object implement
`TranslatableInterface` (see `TaxType`, `ScheduleEndType`).

**Emails and notifications** — subjects are keys in the `email` domain, translated when the
message is built (`NotificationMessage::getSubjectKey()`, `ReminderSubjectListener`). Plain-text
email templates wrap their body in `{% autoescape false %}` — Twig escapes `*.text.twig` as
HTML, which would turn `don't` into `don&#039;t`.

**Dates** — month and day names are text too. In Twig use `twig/intl-extra`
(`value|format_date('long')`, `|format_datetime('medium', 'short')`, or
`|format_date(pattern: 'MMMM d')` when no style fits), never `|date('F j, Y')`. In PHP use
`CarbonImmutable::instance($date)->locale(Locale::getDefault())->translatedFormat('F j')`.
Numeric formats (`Y-m-d`) are fine as they are.

**JavaScript** — there is no client-side translator. Pass translated strings to Stimulus
controllers as values from the template that mounts them:

```twig
<div data-controller="global-search"
     data-global-search-no-results-value="{{ 'search.no_results'|trans }}">
```

## Linting

CI (`.github/workflows/translations-lint.yml`) runs:

```bash
bin/console lint:yaml translations
bin/console lint:translations --locale=en     # message syntax, incl. ICU
bin/console lint:twig src templates
bin/phpunit src/CoreBundle/Tests/Translation/TranslationCatalogTest.php
```

`TranslationCatalogTest` fails when:

- keys are not sorted, or are not lowercase snake_case dotted ids (no bare top-level words);
- a non-ICU catalog uses the legacy plural syntax;
- a literal key passed to `|trans`, `trans()`, `t()`, `TranslatableMessage`, `addFlash()`,
  `->label()`, `buildViolation()` or a form `label`/`help`/`placeholder` option is not defined,
  or is natural-language text instead of a key;
- a template contains hard-coded text (brand names and similar go in its allowlist);
- a catalog key is not referenced anywhere. Keys built at runtime (`'status.' ~ value`) are
  covered by its `DYNAMIC_PREFIXES` list — add the prefix there when you introduce one.

## Adding a new language

1. Add the locale to the list in `config/packages/translation.php`.
2. Provide the translated catalogs (or let the translation provider pull them — see below).
   Missing strings fall back to English automatically.

## Keeping the English catalog in sync

The English source catalog is **maintained by hand**: when you add a `'x.y'|trans` call,
add the `x.y` key to `translations/messages.en.yml` (or the relevant domain) in the same
change. Add it in the correct alphabetical position.

Do **not** run `translation:extract` with `--force`/`--clean` to prune the catalog. Its
static analysis only detects `->trans()`/`t()`/`TranslatableMessage`/constraint messages —
it does **not** see form `label`/`help`/`placeholder` options, `addFlash()`, DataGrid
`->label()` calls, menu labels, enum `translationKey()`, or dynamically built keys. Running
it with cleaning enabled therefore **deletes live strings**.

Use it only in read-only mode to discover keys you referenced but forgot to define:

```bash
bin/console translation:extract en --dump-messages --no-fill   # list, don't write
```

There is also a custom **menu-label extractor**
(`SolidInvoice\CoreBundle\Translation\Extractor\MenuLabelExtractor`) that surfaces the
labels passed to KnpMenu `addChild()` calls, which the built-in extractor skips.

## Pushing / pulling with a provider

The provider is configured generically via a DSN, so it can be swapped without code
changes. Set the DSN (empty by default — no provider is contacted unless configured):

```dotenv
# Crowdin example; swap the scheme for any other Symfony translation provider
SOLIDINVOICE_TRANSLATION_DSN=crowdin://PROJECT_ID:API_TOKEN@ORGANIZATION_DOMAIN.default
```

Then:

```bash
bin/console translation:push   crowdin --force               # upload the English source
bin/console translation:pull   crowdin --force --format=yml   # download translations
```

`translation:pull` writes every locale into the `translations/` directory alongside the
English source, so the round-trip stays in one place.

External contributors who prefer not to run the app can translate directly in the
provider's UI, or edit `translations/<domain>.<locale>.yml` and open a pull request.
