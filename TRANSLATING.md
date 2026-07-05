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

Translation files are YAML, one per bundle, under:

```
src/<Bundle>/Resources/translations/<domain>.<locale>.yml
```

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

Because Symfony merges every bundle's catalog for a domain, a key defined in `CoreBundle`
is available everywhere.

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

## Extracting strings

Symfony's extractor plus a custom **menu-label extractor**
(`SolidInvoice\CoreBundle\Translation\Extractor\MenuLabelExtractor`, which surfaces the
labels passed to KnpMenu `addChild()` calls that the built-in extractor skips) keep the
catalogs in sync with the code:

```bash
bin/console translation:extract en SolidInvoiceInvoiceBundle --domain=messages --dump-messages
bin/console debug:translation en SolidInvoiceInvoiceBundle --domain=messages --only-missing
```

## Pushing / pulling with a provider

The provider is configured generically via a DSN, so it can be swapped without code
changes. Set the DSN (empty by default — no provider is contacted unless configured):

```dotenv
# Crowdin example; swap the scheme for any other Symfony translation provider
SOLIDINVOICE_TRANSLATION_DSN=crowdin://PROJECT_ID:API_TOKEN@ORGANIZATION_DOMAIN.default
```

Then:

```bash
bin/console translation:push   crowdin --force            # upload source strings
bin/console translation:pull   crowdin --force --format=yml   # download translations
```

External contributors who prefer not to run the app can translate directly in the
provider's UI, or edit the `*.<locale>.yml` catalog files and open a pull request.
