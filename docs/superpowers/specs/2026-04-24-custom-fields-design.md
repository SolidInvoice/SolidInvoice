# Custom Fields for Clients and Contacts — Design

**Date:** 2026-04-24
**Target branch:** custom-client-info (off 2.3.x for release, work continues toward 3.x)
**Status:** Design approved, ready for implementation plan.

---

## 1. Goal

Let users define their own fields on `Client` and `Contact` records, beyond the built-in properties. Fields are company-scoped (no cross-company leakage). Users manage the field definitions in a dedicated settings page, fill in values when creating or editing a client/contact, and see the values on the client view page. API and MCP expose both field definitions (CRUD) and field values (embedded on Client/Contact payloads).

**Out of scope for this iteration:** rendering custom fields on invoices, quotes, PDFs, or emails. A later feature will let users choose which fields appear on invoice/quote documents; this design just establishes the data layer and the in-app UI.

---

## 2. Approach Chosen

A unified `CustomField` (definition) + `CustomFieldValue` (value) pair with a `target` discriminator pointing to the entity type (`CLIENT`, `CONTACT`, extensible). The existing `ContactType` + `AdditionalContactDetail` pair — functionally a subset of this design — is migrated into the unified tables and then removed. Any existing seeded channels (additional email, phone, mobile) become ordinary user-managed `CustomField` rows after migration.

Rejected alternatives:
- **Keep and mirror** (separate `ClientField`/`AdditionalClientDetail`): more duplication, two parallel subsystems, two admin UIs.
- **Leave existing system alone, build new alongside**: two overlapping concepts ("additional contact detail" vs "custom field") confusing for users and developers.

---

## 3. Data Model

### 3.1 `CustomField` — field definition

Location: `src/CoreBundle/Entity/CustomField/CustomField.php`. Kept in `CoreBundle` because it's a cross-cutting concern, not domain-specific.

| Column | Type | Notes |
|---|---|---|
| `id` | ULID | PK, app-generated |
| `company_id` | FK → Company | From `CompanyAware` trait; `CompanyFilter` applies automatically |
| `target` | VARCHAR(32), backed enum | `CustomFieldTarget`: `CLIENT`, `CONTACT` (extensible) |
| `label` | VARCHAR(125) NOT NULL | Human-readable label shown in UI |
| `field_key` | VARCHAR(64) NOT NULL | Stable machine key (e.g. `department`); unique per `(company, target)`; used in API payloads |
| `type` | VARCHAR(32), backed enum | `CustomFieldType`: `TEXT`, `TEXTAREA`, `NUMBER`, `DATE`, `EMAIL`, `URL`, `CHECKBOX`, `SELECT`, `MULTI_SELECT` |
| `options` | JSON nullable | Array of `{value, label}` for `SELECT`/`MULTI_SELECT`; null otherwise |
| `required` | BOOL NOT NULL DEFAULT FALSE | Form-level requirement (see §3.4) |
| `position` | INT NOT NULL DEFAULT 0 | Ordering within `(company, target)` |
| `created_at` / `updated_at` | TIMESTAMP | From `TimeStampable` trait |

Indexes:
- `idx_custom_field_company_target_pos` on `(company_id, target, position)` — for ordered form/view fetch (hot path)
- `uq_custom_field_company_target_key` unique on `(company_id, target, field_key)` — key collision prevention

No `Archivable` trait for v1 — per the decision in §10, deletion is hard-cascade after user confirmation. If we later want to preserve values on delete, add `archived_at` as a non-breaking change.

### 3.2 `CustomFieldValue` — field value

Location: `src/CoreBundle/Entity/CustomField/CustomFieldValue.php`.

| Column | Type | Notes |
|---|---|---|
| `id` | ULID | PK |
| `company_id` | FK → Company | From `CompanyAware` trait |
| `field_id` | FK → CustomField, ON DELETE CASCADE | Deleting a field cascades its values |
| `target` | VARCHAR(32), backed enum | `CustomFieldTarget`, denormalized for index efficiency |
| `target_id` | ULID NOT NULL | Polymorphic — the Client or Contact ULID. No FK constraint (see §3.3) |
| `value` | TEXT nullable | Single text column, app-level casting per `field.type` (§4.2) |
| `created_at` / `updated_at` | TIMESTAMP | From `TimeStampable` |

Indexes:
- `idx_cfv_company_target_record` on `(company_id, target, target_id)` — hot path: fetch all values for a single client/contact
- `idx_cfv_field` on `(field_id)` — used by delete-with-values confirmation (usage count) and the `ON DELETE CASCADE`
- `uq_cfv_field_record` unique on `(field_id, target_id)` — one value per field per record

### 3.3 Polymorphic `target_id` — no DB FK, app-enforced integrity

We deliberately avoid separate `client_id` / `contact_id` columns. The `(target, target_id)` pair scales cleanly to more entity types later and keeps the schema clean. Trade-off: no DB-level FK from `CustomFieldValue.target_id` to `Client.id` / `Contact.id`. Compensation:

1. **Doctrine `postRemove` listener** on `Client` and `Contact`: when a record is deleted, delete all `CustomFieldValue` rows with matching `(target, target_id)`.
2. **App-level guard on write**: the state processor and form data mapper verify the target record exists and belongs to the current company before persisting a value.
3. **Integrity check command** `bin/console app:custom-fields:check-orphans` — one-off maintenance tool that finds and optionally cleans orphan `CustomFieldValue` rows. Simple; covers operational recovery if a listener ever fails to fire.

The codebase already accepts this trade-off elsewhere (e.g. `NotificationBundle` transport settings).

### 3.4 Required fields — form-level only

`required = true` applies at the form layer (a `NotBlank` constraint added by `CustomFieldTypeResolver`), not as a DB NOT NULL. Semantics:

- Creating a new record with a required field unfilled → validation error.
- Editing a pre-existing record (created before the field existed) that has no value → the form renders the required field as required; the user must fill it before saving. **This is the desired behavior per the brainstorm decision** — it surfaces the missing data rather than hiding it.
- API `PATCH` that doesn't include a required field key → no change (leaves whatever was there). Required is enforced on the fields the user *is* writing, not globally.

### 3.5 Company scoping

Both entities use `CompanyAware`. The existing `CompanyFilter` SQLFilter handles all read paths automatically. Write paths (form, API, MCP) set the company from the authenticated user via the usual pattern.

---

## 4. Field types and value handling

### 4.1 `CustomFieldType` enum

```php
enum CustomFieldType: string {
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case DATE = 'date';
    case EMAIL = 'email';
    case URL = 'url';
    case CHECKBOX = 'checkbox';
    case SELECT = 'select';
    case MULTI_SELECT = 'multi_select';
}
```

### 4.2 `CustomFieldTypeResolver` service

Single service, one method per concern, encapsulates all type-specific behavior so form types, API normalizers, and view rendering share one source of truth.

```php
final class CustomFieldTypeResolver {
    public function buildFormField(FormBuilderInterface $builder, CustomField $field): void;
    public function constraints(CustomField $field): array;
    public function serialize(CustomField $field, mixed $input): ?string;   // UI input  → TEXT column
    public function deserialize(CustomField $field, ?string $stored): mixed; // TEXT column → typed PHP
    public function formatForDisplay(CustomField $field, ?string $stored): string; // TEXT → rendered
}
```

Storage format in the single `value TEXT` column:

| Type | Stored as | Deserialized PHP type |
|---|---|---|
| TEXT / TEXTAREA / EMAIL / URL | string as-is | `string` |
| NUMBER | numeric string (preserves precision) | `int|float` (on read) |
| DATE | ISO 8601 `YYYY-MM-DD` | `DateTimeImmutable` |
| CHECKBOX | `"0"` or `"1"` | `bool` |
| SELECT | option value | `string` |
| MULTI_SELECT | JSON array of option values | `array<string>` |

### 4.3 Form widget: `CustomFieldValueCollectionType`

Reusable form type embedded by both `ClientType` and `ContactType`. Options:
- `target: CustomFieldTarget` (required)

Behavior:
1. On build, query `CustomField` rows for `(company, target)`, ordered by `position`.
2. For each definition, call `$resolver->buildFormField(...)` with the field's specific form child name (`field_key`).
3. Attach constraints from `$resolver->constraints(...)`.
4. Implement `DataMapperInterface`:
   - `mapDataToForms()`: indexes the parent record's `CustomFieldValue` collection by `field.field_key`, populates children via `$resolver->deserialize(...)`.
   - `mapFormsToData()`: reads children, upserts `CustomFieldValue` entities via `$resolver->serialize(...)`; clears (deletes) values set to empty.

If no definitions exist for the company+target, the collection type renders nothing (empty fragment). The parent template uses a guard so the surrounding card is hidden too.

---

## 5. Settings Page — managing field definitions

### 5.1 Route and layout

- Route: `/settings/custom-fields`
- Nav: link added under the existing Settings menu
- Permission: matches existing Settings pages (`ROLE_ADMIN` or whatever `SettingsBundle` already uses — match, don't invent)

Page structure:
- Page header: "Custom fields"
- Tabler tabs: "Client fields" / "Contact fields"
- Each tab: reorderable list + "+ Add field" button (Tabler `btn btn-primary`)

### 5.2 List row

```
[≡]  Department              Text         Required          ⋮ Edit · Delete
     field_key: department    3 values
```

- `[≡]` drag handle on the left
- Label, type badge, required badge, usage count (how many records have a value — one count query grouped by field)
- Row actions: Edit (opens modal), Delete (opens confirm modal)

### 5.3 Add/Edit modal (LiveComponent)

Implemented as a Symfony UX LiveComponent because the options editor for `SELECT`/`MULTI_SELECT` needs to appear/disappear dynamically based on the selected type.

Fields:
- **Label** — free text → auto-suggests `field_key` (slugified, lowercased, underscored), editable
- **Type** — dropdown of the 9 cases
- **Required** — checkbox
- **Options editor** — visible only for `SELECT` / `MULTI_SELECT`: repeatable rows of `value` + `label`, reorderable, with "+ Add option" and per-row delete

Submit POSTs to `CreateAction` / `EditAction` which persist the `CustomField` and redirect back to the list.

### 5.4 Reorder

A Stimulus controller (`custom-field-reorder_controller.js`) with `Sortable.js` for drag-and-drop. On drop, the controller optimistically updates the DOM and POSTs to `/settings/custom-fields/reorder` with `[{id, position}, ...]`. The endpoint updates positions in one transaction and returns 204. On error, the controller reverts and shows a toast.

**Not a LiveComponent** for the list itself — LiveComponents re-render the whole list on every interaction, which fights drag-and-drop. Plain Stimulus + small JSON endpoint is simpler.

### 5.5 Delete confirmation

1. Click Delete on a row
2. Modal: `"Delete 'Department'? N client records have a value for this field. These values will be permanently deleted. This cannot be undone."` (N omitted when zero)
3. Confirm requires clicking a destructive "Yes, delete" button (no type-to-confirm — overkill for this scope)
4. On confirm: `DELETE /settings/custom-fields/{id}` → hard delete; `ON DELETE CASCADE` removes `CustomFieldValue` rows

### 5.6 Empty states

"No custom fields yet — click **Add field** to create one." with a Tabler empty-state icon.

### 5.7 Visual design

Follows existing Tabler patterns already in the codebase (cards, modals, icons, buttons). No `/frontend-design` skill needed — this is well-established CRUD-with-reorder.

---

## 6. Client and contact form integration

### 6.1 Client form (`ClientType`)

Add one child:

```php
$builder->add('customFields', CustomFieldValueCollectionType::class, [
    'target' => CustomFieldTarget::CLIENT,
    'label'  => false,
]);
```

Rendered as a separate card section in the Client edit template titled "Custom fields", below the name/website/currency/VAT card. Hidden when no `CLIENT`-target definitions exist.

### 6.2 Contact subform (`ContactType`)

Same treatment with `target: CONTACT`. Slots into each contact's accordion inside the `LiveCollectionType`.

### 6.3 Removal of `additionalContactDetails`

With the migration in §9, we remove:
- `Contact::$additionalContactDetails` collection and its accessor methods
- `AdditionalContactDetail` entity
- `ContactType` entity (the old one, not to be confused with the form class)
- `ContactDetailType` and `ContactDetailCollectionType` form classes
- The `ContactInfo.html.twig` block that rendered them

The new `CustomFieldValueCollectionType` with `target: CONTACT` replaces the UX entirely — whatever the user was managing as "additional contact details" is now managed as custom fields.

### 6.4 Entity relation mapping

Client and Contact entities gain a helper `getCustomFieldValues(): Collection` on their repositories (not via Doctrine `OneToMany`, since `target_id` is polymorphic). The helper queries `CustomFieldValue WHERE target = ? AND target_id = ?`.

`cascade remove` on Client/Contact deletion is handled by a Doctrine `postRemove` listener (see §3.3), not by ORM `cascade: ['remove']` — the relation isn't declared as an association so there's no ORM cascade to hook into.

---

## 7. View page rendering

### 7.1 Reusable Twig component

A `CustomFieldsList` Twig component in `src/CoreBundle/Twig/Components/CustomFieldsList.php`:

```twig
<twig:CustomFieldsList :record="client" target="CLIENT" />
```

- Takes a record (Client or Contact) and a `CustomFieldTarget`
- Fetches the company's `CustomField` definitions for that target, ordered by `position`
- Fetches the record's `CustomFieldValue` rows, indexed by `field_id`
- Renders one row per definition (so missing values on old records are visible as "—")
- Hidden entirely when the company has no definitions for that target

### 7.2 Type-aware formatting

| Type | Display |
|---|---|
| TEXT / EMAIL / URL / SELECT | Plain; email as `mailto:`, URL as clickable link, SELECT as the option's `label` |
| TEXTAREA | `|nl2br` to preserve line breaks |
| NUMBER | As-is (localized formatting if convenient via Twig filters) |
| DATE | `|format_date` localized |
| CHECKBOX | ✓ / — |
| MULTI_SELECT | Comma-separated labels resolved from `options` |

### 7.3 Embed points

- `ClientBundle/Resources/views/Default/view.html.twig`: new card between hero and contacts
- `ClientBundle/Resources/views/Components/ContactInfo.html.twig`: inside each contact's details card (replaces the old `additionalContactDetails` loop)

### 7.4 Edit pages

No view-specific templates. `ClientType` / `ContactType` forms already embed `CustomFieldValueCollectionType`, so edit pages pick them up automatically.

---

## 8. API and MCP

### 8.1 `CustomField` as API Resource

`src/CoreBundle/Entity/CustomField/CustomField.php` with `#[ApiResource]`, MCP enabled on every operation:

| Op | Method/Path |
|---|---|
| List | `GET /api/custom_fields?target=CLIENT` |
| Get | `GET /api/custom_fields/{id}` |
| Create | `POST /api/custom_fields` |
| Update | `PATCH /api/custom_fields/{id}` |
| Delete | `DELETE /api/custom_fields/{id}` |
| Reorder | `POST /api/custom_fields/reorder` (custom operation; body `[{id, position}, ...]`) |

`CompanyFilter` handles scoping. Validation (unique `field_key` per `(company, target)`, options required for SELECT types, etc.) uses the same constraints as the settings form.

### 8.2 Custom field values on Client and Contact payloads

No separate value resource — values are embedded:

```json
GET /api/clients/{id}
{
  "id": "...",
  "name": "Acme Corp",
  "customFields": {
    "department": "Sales",
    "contract_start": "2026-01-15",
    "tier": "gold",
    "tags": ["priority", "enterprise"]
  }
}
```

- `customFields` is a flat object keyed by `field_key`, values typed per field type
- On `POST` / `PATCH`: the same shape; state processor upserts `CustomFieldValue` rows after the entity saves
- Unknown keys → 422 with a clear error naming the unknown keys
- Missing keys on PATCH → unchanged
- Explicit `null` → clear the value (subject to required validation)

Implementation:
- `CustomFieldsNormalizer`: reads the record's values, deserializes each through `CustomFieldTypeResolver`, outputs the object under the `customFields` key
- `CustomFieldsDenormalizer`: reads the object, looks up each field by `field_key` (within company+target), serializes the value, stages updates
- `CustomFieldsStateProcessor`: after the entity persists, applies the staged updates to `CustomFieldValue` (upsert, clear, delete)

Follows the pattern of the existing `MoneyNormalizer` / `DiscountNormalizer`.

### 8.3 OpenAPI schema

`customFields` declared as `additionalProperties: true` with a description pointing callers to `GET /api/custom_fields` to discover keys. A dynamic OpenAPI contributor adds this — the project already uses API Platform 4 contributors elsewhere.

### 8.4 MCP

API Platform 4's MCP support auto-generates tools from operations with `mcp: true`. Tools available to agents after this work:
- `list_custom_fields`, `get_custom_field`, `create_custom_field`, `update_custom_field`, `delete_custom_field`, `reorder_custom_fields`
- `list_clients` / `get_client` / `create_client` / `update_client` — all include `customFields` in their schemas (dynamic, additionalProperties)
- Same for contacts

Each tool's MCP description mentions the dynamic `customFields` bag and points agents at `list_custom_fields` for discovery.

---

## 9. Migration from `ContactType` / `AdditionalContactDetail`

Single Doctrine migration (next in the `Version30xxx_N` sequence based on current state — likely `Version30100_1.php`).

### 9.1 Forward (`up()`)

1. **Create `custom_field` table** with the schema from §3.1
2. **Create `custom_field_value` table** with the schema from §3.2
3. **Copy `contact_type` → `custom_field`**:
   - `id`, `company_id` preserved (ULIDs remain stable → external references keep working)
   - `target = 'CONTACT'`
   - `label = old.name`
   - `field_key = slugify(old.name)` — lowercase, non-alnum → `_`, collapse runs of `_`; on rare per-company duplicates append `_2`, `_3`
   - `type`: map old string (`'text'`/`'email'`/unknown) to the new enum (unknown → `TEXT`)
   - `options`: re-encode old `field_options` to `[{value, label}]` where non-empty; null otherwise
   - `required = old.required`
   - `position = ROW_NUMBER() OVER (PARTITION BY company_id ORDER BY id)`
   - Timestamps: now
4. **Copy `additional_contact_detail` → `custom_field_value`**:
   - `id`, `company_id` preserved
   - `field_id = old.type_id`
   - `target = 'CONTACT'`
   - `target_id = old.contact_id`
   - `value = old.value`
   - Timestamps: now
5. **Drop FKs and tables** `additional_contact_detail`, `contact_type`
6. **Remove `Contact.additionalContactDetails` relation** (entity class changes committed in the same PR — no separate migration needed since the FK is already dropped)

### 9.2 Down

`down()` throws `IrreversibleMigrationException` — matching the codebase convention for structural migrations. Reversing would require format coercion and we'd lose the richer types.

### 9.3 Data safety

- Zero data loss in the forward direction (every old row maps to exactly one new row)
- Migration docblock includes: "Back up your database before running. This migration restructures contact types."
- Dedicated migration test (§10.3) seeds the old schema, runs the migration, asserts mapping

### 9.4 Breaking changes

- The `additionalContactDetails` array on contact API payloads is replaced by the `customFields` object keyed by `field_key`. Documented in release notes.
- Aligned with 3.x major versioning — breaking API change in a major release is conventional.

---

## 10. Lifecycle and edge cases

### 10.1 Deleting a field with existing values

Per the brainstorm decision: show the confirmation modal with the usage count, and on confirm hard-cascade both the definition and all its values via `ON DELETE CASCADE`. No soft-archive.

### 10.2 Required field added later

Per §3.4: applies to new records, and to edits of existing records (the form blocks save until filled). No retroactive hard DB constraint.

### 10.3 Per-field validation beyond "required" + type

Out of scope for v1. Skipped per the brainstorm decision.

### 10.4 Field rename

Changing `label` is safe. Changing `field_key` changes the API contract — we allow it (it's user-managed data) but the settings modal shows a warning: "Changing the key will break any external integrations using it." No rename cascading to stored values is needed; values are linked by `field_id`.

### 10.5 Type change on a field with existing values

Not addressed in v1 UI — the edit modal disables the Type dropdown once the field has values, with a hint "Delete and recreate to change the type." Simple, avoids the combinatorial headache of type-to-type coercion.

### 10.6 Options removed from a SELECT/MULTI_SELECT

If a user removes an option that existing values reference, those values become orphaned (still present in storage, but render as their raw `value` rather than a label, since lookup misses). The modal warns on save: "Removing option 'gold' will affect N records that currently use it." No automatic cleanup — keeps user in control.

---

## 11. Testing

### 11.1 Unit tests

- `CustomFieldType` enum: each case's `buildFormField`, `constraints`, `serialize`/`deserialize` round-trip including edge cases (empty string, null, whitespace-only, multi-select with empty array)
- `CustomFieldsNormalizer` / `CustomFieldsDenormalizer`: round-trip, unknown keys → validation error, null clears, missing keys no-op on PATCH
- `CustomFieldValueCollectionType`: `mapDataToForms` / `mapFormsToData` round-trip; upsert/clear/required-but-empty; re-rendering when a new field definition appears between load and submit

### 11.2 Functional tests

- Settings page: unauth redirect; wrong company sees empty list; create/edit/delete; reorder endpoint updates positions; delete-with-values confirmation shows usage count
- Client create/edit with custom fields rendered: happy path, required-empty validation error, invalid type value validation error
- Contact create/edit: same coverage for contact-target fields
- Client view page: type-aware formatting (date localized, checkbox ✓, URL as link)
- Company scoping: A's fields invisible/invalid to B across all paths (list, get, write, value key)

### 11.3 API tests

- Full CRUD on `/api/custom_fields` with `target` filter
- `POST /api/clients` with `customFields` payload persists; values re-appear on GET
- `PATCH /api/clients/{id}` partial update; null clears; unknown key → 422
- Reorder endpoint
- Cross-company isolation via API tokens

### 11.4 Migration test

Seed old-schema rows (mixed types, with options, with values) via raw SQL → run migration → assert new rows land correctly, old tables gone. This is the highest-risk piece; dedicated coverage catches mapping bugs pre-prod.

### 11.5 MCP smoke test

One test: boot API Platform's MCP endpoint, assert expected tool names are listed, call `list_custom_fields` and `create_client` with `customFields`, assert persistence. Not exhaustive — just proves the MCP surface is alive.

---

## 12. File touch list (roughly)

**New:**
- `src/CoreBundle/Entity/CustomField/CustomField.php`
- `src/CoreBundle/Entity/CustomField/CustomFieldValue.php`
- `src/CoreBundle/Enum/CustomFieldType.php`
- `src/CoreBundle/Enum/CustomFieldTarget.php`
- `src/CoreBundle/Repository/CustomFieldRepository.php`
- `src/CoreBundle/Repository/CustomFieldValueRepository.php`
- `src/CoreBundle/Service/CustomField/CustomFieldTypeResolver.php`
- `src/CoreBundle/Form/Type/CustomFieldValueCollectionType.php` (value widget embedded in Client/Contact forms)
- `src/SettingsBundle/Form/Type/CustomFieldDefinitionType.php` (definition form used in the settings modal; named with `Definition` to avoid collision with the `CustomFieldType` enum)
- `src/CoreBundle/Twig/Components/CustomFieldsList.php` + template (cross-cutting, used by Client and Contact views)
- `src/SettingsBundle/Twig/Components/CustomFieldEditModal.php` + template (LiveComponent, settings-specific)
- `src/CoreBundle/Listener/CustomFieldValueCleanupListener.php` (postRemove on Client/Contact)
- `src/CoreBundle/Command/CustomFieldOrphanCheckCommand.php`
- `src/SettingsBundle/Action/CustomField/{Index,Create,Edit,Delete,Reorder}.php`
- `src/SettingsBundle/Resources/views/CustomField/index.html.twig`
- `src/CoreBundle/Serializer/CustomFields{Normalizer,Denormalizer}.php`
- `src/CoreBundle/State/CustomFieldsStateProcessor.php`
- `src/CoreBundle/OpenApi/CustomFieldsSchemaDecorator.php`
- `assets/controllers/custom-field-reorder_controller.js`
- `migrations/Version30100_1.php` (structural migration from `contact_type`/`additional_contact_detail`)
- `src/InstallBundle/...` fresh-install seeder entry that creates `additional_email`/`phone`/`mobile` CONTACT-target fields (follow existing install fixture pattern)
- Tests across the above

**Modified:**
- `src/ClientBundle/Entity/Client.php` — add `getCustomFieldValues()` helper (or via repository)
- `src/ClientBundle/Entity/Contact.php` — remove `$additionalContactDetails`, add `getCustomFieldValues()`
- `src/ClientBundle/Form/Type/ClientType.php` — add `customFields` child
- `src/ClientBundle/Form/Type/ContactType.php` — remove old `additionalContactDetails`, add `customFields` child
- `src/ClientBundle/Resources/views/Default/view.html.twig` — embed `<twig:CustomFieldsList />`
- `src/ClientBundle/Resources/views/Components/ContactInfo.html.twig` — swap old loop for `<twig:CustomFieldsList />`
- `src/MenuBundle/...` — add "Custom fields" to Settings nav

**Removed (after migration lands):**
- `src/ClientBundle/Entity/ContactType.php`
- `src/ClientBundle/Entity/AdditionalContactDetail.php`
- `src/ClientBundle/Repository/ContactTypeRepository.php`
- `src/ClientBundle/Form/Type/ContactDetailType.php`
- `src/ClientBundle/Form/Type/ContactDetailCollectionType.php`
- `src/ClientBundle/Resources/views/Form/contact_details.html.twig`

---

## 13. Non-blocking notes

- **Default seeder for fresh installs.** The migration in §9 covers existing installs by copying their `contact_type` rows. Fresh installs start with zero custom fields, which is a worse first-run experience than today's "email, phone, mobile already exist" defaults. Decision: **include a fresh-install seeder** (fixture or installer step) that creates three CONTACT-target fields — `additional_email` (EMAIL), `phone` (TEXT), `mobile` (TEXT) — matching today's defaults.
- **Export / import of field definitions across companies.** Out of scope for v1; potential later enhancement.
