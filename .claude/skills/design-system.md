# SolidInvoice Design System

Guidelines for implementing consistent, modern UI. **All UI changes MUST follow these guidelines.**

## Philosophy

Clean & Minimal aesthetic: content-focused, 8px spacing scale, subtle interactions, accessible by default.

## Design Tokens

All CSS custom properties use the `--tblr-` prefix. Defined in `/assets/scss/design-system/_tokens.scss`.

That prefix is Tabler's, not ours, and it is deliberate: Tabler 1.5 dropped the `$prefix` Sass
variable, so a token named after one of Tabler's overwrites it outright. Setting `--tblr-primary`
recolours every button, link and badge Tabler builds from it — there is no separate app namespace
and no mapping layer. Tokens with no Tabler counterpart (`--tblr-space-4`) live here too.

### Colors

| Token                                | Usage                           |
|--------------------------------------|---------------------------------|
| `--tblr-primary` (#2e963a)            | Main CTAs, links, active states |
| `--tblr-primary-dark` (#1f6c29)       | Hover/active states             |
| `--tblr-primary-light` (#e8f5e9)      | Backgrounds, badges             |
| `--tblr-secondary` (#f0a015)          | Accent (use sparingly)          |
| `--tblr-success/danger/warning/info`  | Status colors                   |
| `--tblr-gray-50` to `--tblr-gray-900`  | Text and backgrounds            |
| `--tblr-text-primary/secondary/muted` | Text hierarchy                  |

### Spacing (8px base)

`--tblr-space-1` (4px), `--tblr-space-2` (8px), `--tblr-space-3` (12px), `--tblr-space-4` (16px), `--tblr-space-6` (24px), `--tblr-space-8` (32px)

### Border Radius

`--tblr-radius-sm` (6px), `--tblr-radius-md` (8px), `--tblr-radius-lg` (12px), `--tblr-radius-xl` (16px)

### Shadows

`--tblr-shadow-sm` (cards), `--tblr-shadow-md` (hover/dropdowns), `--tblr-shadow-lg` (modals), `--tblr-shadow-primary` (button glow)

## Typography

| Element       | Size              | Weight        |
|---------------|-------------------|---------------|
| Page title    | `--tblr-text-2xl`  | semibold      |
| Card title    | `--tblr-text-lg`   | semibold      |
| Body          | `--tblr-text-base` | normal        |
| Help/labels   | `--tblr-text-sm`   | normal/medium |
| Table headers | `--tblr-text-xs`   | semibold      |

## Platform UI Components (REQUIRED)

**Always use SolidWorx/Platform UI components when available:**

| Component         | Usage           |
|-------------------|-----------------|
| `<twig:Ui:Card>`  | Card containers |
| `<twig:Ui:Alert>` | Notifications   |
| `<twig:Ui:Modal>` | Dialogs         |

Docs in `vendor/solidworx/platform/src/Bundle/Ui/Docs/`

### Card Example

```twig
<twig:Ui:Card title="Title" subtitle="Subtitle">
    <p>Content</p>
    <twig:block name="footer">
        <button class="btn btn-ghost">Cancel</button>
        <button class="btn btn-primary">{{ ux_icon('tabler:device-floppy') }} Save</button>
    </twig:block>
</twig:Ui:Card>
```

### Alert Example

```twig
<twig:Ui:Alert type="success" icon="tabler:check" title="Saved!" :dismissible="true">
    Message here
</twig:Ui:Alert>
```

### Modal Example

```twig
<twig:Ui:Modal id="confirm-modal" title="Confirm" status="danger">
    <p>Content</p>
    <twig:block name="footer">
        <button class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger">Confirm</button>
    </twig:block>
</twig:Ui:Modal>
```

## Buttons

| Class                 | Usage                    |
|-----------------------|--------------------------|
| `btn-primary`         | Main action, form submit |
| `btn-ghost`           | Cancel, minimal actions  |
| `btn-outline-primary` | Secondary actions        |
| `btn-danger`          | Destructive actions ONLY |

Icon before text: `{{ ux_icon('tabler:device-floppy') }} Save`

## Form Actions Pattern (CRITICAL)

**Every form MUST follow:**

```html

<div class="card-footer">
    <div class="form-actions">
        <button class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">
            {{ ux_icon('tabler:device-floppy') }} Save
        </button>
    </div>
</div>
```

- Cancel: ghost/outline, LEFT
- Save: primary + icon, RIGHT
- Use `.form-actions-spread` to push Cancel to far left

## Page Patterns

### Settings Pages

- Max-width 900px, centered (`.settings-page`)
- Horizontal tabs for 2-7 sections
- Sidebar nav for 8+ sections
- Danger zone at bottom with red border

### List Pages

```html

<div class="list-page">
    <div class="list-header"><!-- title + actions --></div>
    <div class="list-filters"><!-- search/filters --></div>
    <div class="list-content">
        <table>...</table>
    </div>
    <div class="list-footer"><!-- pagination --></div>
</div>
```

Empty state: Use `.list-empty` with icon, title, description, and CTA button.

### Form Pages

Wrap form in card with header, body (fields), footer (form-actions).

## Tables

- Headers: uppercase, `--tblr-text-xs`, muted
- Column classes: `.table-actions`, `.table-checkbox`, `.table-date`, `.table-amount`, `.table-id`
- No alternating row colors

## Modals

Sizes: `.modal-sm` (400px), default (500px), `.modal-lg` (800px), `.modal-xl` (1140px)

Delete confirmation: Use `.modal-danger` + `.modal-confirm` with icon, title, message.

## Animation

- Hover: 150-200ms, subtle lift + shadow
- State changes: 200-300ms
- Do NOT animate: text changes, active states, backgrounds

## Accessibility

- Focus states on all interactive elements
- Contrast: 4.5:1 text, 3:1 UI
- Labels on form fields
- `aria-required`, `aria-describedby`, `aria-label` where needed

## Key Files

| File                                          | Purpose              |
|-----------------------------------------------|----------------------|
| `/assets/scss/design-system/_tokens.scss`     | Design tokens        |
| `/assets/scss/design-system/_typography.scss` | Typography           |
| `/assets/scss/_forms.scss`                    | Form enhancements    |
| `/assets/scss/settings.scss`                  | Settings page styles |

## Checklist

1. Use Platform UI components when available
2. Use design tokens, no hardcoded values
3. Follow form actions pattern
4. Appropriate button variants
5. Tables use Tabler/Bootstrap defaults
6. Focus states, ARIA labels, proper contrast
