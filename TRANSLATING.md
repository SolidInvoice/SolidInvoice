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
