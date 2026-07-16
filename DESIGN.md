---
name: SolidInvoice
description: Open-source, self-hostable invoicing for freelancers and small businesses.
colors:
  primary: "#2e963a"
  primary-hover: "#268032"
  primary-dark: "#1f6c29"
  primary-light: "#e8f5e9"
  primary-lighter: "#f1f9f2"
  secondary: "#f0a015"
  secondary-hover: "#d89012"
  secondary-dark: "#c8820e"
  secondary-light: "#fff8e1"
  success: "#10b981"
  success-dark: "#047857"
  success-light: "#d1fae5"
  danger: "#ef4444"
  danger-dark: "#b91c1c"
  danger-light: "#fee2e2"
  warning: "#f59e0b"
  warning-light: "#fef3c7"
  info: "#3b82f6"
  info-light: "#dbeafe"
  body-bg: "#f4f6f8"
  surface: "#ffffff"
  surface-hover: "#f8fafc"
  text-primary: "#1e293b"
  text-secondary: "#475569"
  text-muted: "#64748b"
  text-light: "#94a3b8"
  body-color: "#1e293b"
  border: "#e2e8f0"
  border-light: "#f1f5f9"
  border-dark: "#cbd5e1"
  gray-50: "#f8fafc"
  gray-100: "#f1f5f9"
  gray-200: "#e2e8f0"
  gray-500: "#64748b"
  gray-700: "#334155"
  gray-900: "#0f172a"
typography:
  display:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  title:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.375
    letterSpacing: "normal"
  body:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.625
    letterSpacing: "normal"
  label:
    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.5
    letterSpacing: "0.05em"
  mono:
    fontFamily: "ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace"
    fontSize: "0.8125rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "16px"
  "2xl": "24px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  "2xl": "48px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "#ffffff"
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-ghost:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.lg}"
    padding: "24px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  input-focus:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
  table-header:
    backgroundColor: "{colors.gray-50}"
    textColor: "{colors.text-muted}"
    padding: "16px 24px"
  modal:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.xl}"
    padding: "24px"
  chip-success:
    backgroundColor: "{colors.success-light}"
    textColor: "{colors.success}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  chip-danger:
    backgroundColor: "{colors.danger-light}"
    textColor: "{colors.danger}"
    rounded: "{rounded.full}"
    padding: "2px 10px"
---

# Design System: SolidInvoice

## 1. Overview

**Creative North Star: "The Friendly Desk"**

SolidInvoice should feel like a clean workspace with a touch of warmth — approachable, never corporate. The user comes in expecting admin pain and finds an organized desk instead: paper-white surfaces, soft shadows that suggest depth without performing it, and a quiet green accent that says "you can do this" instead of shouting. The interface treats money with respect (clarity, precision, never hiding totals) while treating the human warmly (plain language, encouraging empty states, no scolding).

The system explicitly rejects four aesthetics. It is not a legacy AdminLTE/Bootstrap admin panel — no dark blue navbar, no sidebar tree of every entity, no boxy widgets. It is not enterprise accounting (QuickBooks, Sage, Xero) — no dense corporate toolbars, no 90s-feeling forms. It is not AI-slop SaaS — no purple gradients, no glass cards, no hero-metric tiles, no gradient text. It is not crypto/fintech neon-on-black. SolidInvoice sits where competent SMB tools live: Linear's restraint applied to invoicing.

**Key Characteristics:**

- **System fonts, never web fonts.** Native feel, zero load cost, looks correct on every platform.
- **One accent green, used deliberately.** Trust Green (#2e963a) carries primary CTAs and positive money states. Secondary orange exists but appears sparingly.
- **Slate-based neutrals.** Cool gray scale (gray-50 → gray-900) with a hint of blue. No pure black, no pure white in body text.
- **Soft, rounded shapes.** 8px on buttons and inputs, 12px on cards, 16px+ on modals. Nothing sharp; nothing fully circular except pill badges.
- **8px spacing grid.** Half-steps allowed (2px, 6px, 10px, 14px) for fine-tuning.
- **Tabular figures in money.** Numbers in tables and totals align in clean columns.

## 2. Colors

A slate-and-paper palette with a single warm-green accent. The neutral scale carries 90% of the surface area; the green appears only where it does work.

### Primary

- **Trust Green** (`#2e963a` / oklch(57.6% 0.156 142)): The single accent. Used on primary CTAs ("Create Invoice", "Send", "Record Payment"), active nav states, "Paid" badges, focus borders on form fields, and links. A confident, slightly muted forest green — money-positive without being neon. Variants: `primary-hover` (#268032), `primary-dark` (#1f6c29) for active states, `primary-light` (#e8f5e9) for selected-row tints and success alert backgrounds, `primary-lighter` (#f1f9f2) for ambient hover surfaces.

### Secondary

- **Warm Amber** (`#f0a015`): Used *sparingly* for non-primary attention — secondary buttons, "Sent" or "Pending" badges, secondary CTA accents on marketing-adjacent surfaces. Never paired with primary green at high saturation on the same screen; the two are alternates, not partners.

### Tertiary (Semantic / Status)

These four colors are reserved for status meaning and must never be used decoratively.

They are **fill** colors, not **text** colors. At full saturation none of them clears 4.5:1 on `surface` (measured: success 2.54:1, danger 3.76:1, warning and info lower still). Use them for chip backgrounds, icon fills, and borders; when a status needs to be rendered as *text*, use the `-dark` step. See The Legible Status Rule below.

- **Success** (`#10b981`): Distinct from primary green. Used on payment confirmation toasts, "Settled" states, and positive deltas. Pair with `success-light` (#d1fae5) for backgrounds. For success-colored text use **`success-dark`** (`#047857`, emerald-700 — 5.48:1 on surface, 5.06:1 on body-bg).
- **Danger** (`#ef4444`): Overdue invoices, destructive confirmations, validation errors. Pair with `danger-light` (#fee2e2). For danger-colored text use **`danger-dark`** (`#b91c1c`, red-700 — 6.47:1 on surface, 5.97:1 on body-bg).
- **Warning** (`#f59e0b`): "Due soon", soft alerts, partial-payment hints. Pair with `warning-light` (#fef3c7).
- **Info** (`#3b82f6`): Neutral information, "Draft" badges, system messages. Pair with `info-light` (#dbeafe).

### Neutral

A 10-step slate ramp. Every neutral is tinted cool, not pure gray.

- **Body Background** (`#f4f6f8`): The application canvas. A very soft warm-gray-blue, not white. Cards and inputs sit on top of this.
- **Surface** (`#ffffff`): Cards, modals, dropdowns, inputs. Pure white is acceptable here only because the body is tinted; surfaces appear *as* paper against the canvas.
- **Surface Hover** (`#f8fafc`): The faintest gray-blue. Used for table-row hover and ambient interactive states.
- **Text Primary** (`#1e293b` / gray-800): All primary body text, headings, table cells. Never `#000`. 14.63:1 on surface.
- **Body Color** (`= text-primary`): The default text color. An alias, not a separate value — it exists because `_forms.scss` and `.text-body` consume it by that name. It must never diverge from `text-primary`.
- **Text Secondary** (`#475569` / gray-600): Labels, descriptions, secondary metadata. 7.58:1 on surface, 6.99:1 on body-bg — the darkest "dimmed" step that holds AA on *both* surfaces.
- **Text Muted** (`#64748b` / gray-500): Captions, help text, table-header labels, timestamps. 4.76:1 on surface (passes) but **4.39:1 on `body-bg` (fails AA)**. Safe on white cards, modals, and inputs; not safe directly on the app canvas. Meaningful text placed on `body-bg` must use `text-secondary` instead.
- **Text Light** (`#94a3b8` / gray-400): Disabled text and decorative glyphs **only**. **2.56:1 on surface, 2.37:1 on body-bg — fails WCAG AA (1.4.3).** Disabled controls are exempt from 1.4.3, which is why the token still exists. Placeholders are **not** exempt and must not use it: a placeholder is meaningful text and needs 4.5:1. The token is deliberately left un-retuned — any AA-passing value would collide with `text-muted` and make the two steps redundant, so the fix belongs at the call sites, not the token.
- **Border** (`#e2e8f0` / gray-200): Default form-control and card borders.
- **Border Light** (`#f1f5f9` / gray-100): Table row separators, subtle dividers.

### Named Rules

**The One Accent Rule.** Trust Green carries primary action; Warm Amber carries secondary. They never appear at full saturation on the same screen. If a screen needs both, demote one to its tint (`*-light` family) and keep the other at full color.

**The Tinted Neutrals Rule.** Every "gray" is slate-tinted (cool, slight blue). Pure-gray neutrals (`#cccccc` family) are prohibited. Pure `#000` and `#fff` for text are prohibited; use `text-primary` (#1e293b) and `surface` (#ffffff against the tinted body, never as text on tinted body).

**The Money-Color Rule.** Color on money is informational, never decorative. Green amounts mean "Paid". Red amounts mean "Overdue". Black/text-primary amounts mean "neutral" (Draft, Pending, Sent) — this is the default and the common case. A green total without a "Paid" label is wrong — color alone is never the carrier of meaning.

**The Legible Status Rule.** Status colors are fills, not text. `success`, `danger`, `warning`, and `info` are tuned for chip backgrounds and icon fills, and none of them clears 4.5:1 as text — on white or on their own `-light` tint. Status *text* uses the `-dark` step (`success-dark`, `danger-dark`). This matters most on money: an amount is the one value in the product that must never be hard to read, so the `.money` component binds its colors to AA-safe steps on every surface rather than to the raw status colors.

*Known non-conformance:* the chip variants in §5 still specify raw status text on `-light` tints (measured: Paid 2.24:1, Overdue 3.08:1, Warning 1.93:1, Info 3.01:1 — all fail). Chips were out of scope for the pass that introduced this rule. Repointing Paid and Overdue chips to `success-dark` / `danger-dark` clears them (4.84:1 and 5.30:1); Warning and Info need `-dark` steps that do not exist yet.

## 3. Typography

**Display Font:** System sans (`-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`)
**Body Font:** Same system sans stack.
**Mono Font:** System mono (`ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace`)

**Character:** Native, performant, invisible. SolidInvoice doesn't put a "designer font" between the user and the data. System fonts render the OS's native UI text — on macOS it's San Francisco, on Windows it's Segoe UI, on Linux it's Roboto or Noto Sans. The result is an app that always looks correct, never out-of-place, and loads in zero milliseconds. Body sits at 15px (0.9375rem) — one notch larger than the 14px default for slightly more comfortable reading.

### Hierarchy

- **Display** (600, 30px / 1.875rem, line-height 1.25, tracking -0.025em): Hero titles only — empty-state headlines, first-run wizard step titles. Used rarely.
- **Headline** (600, 24px / 1.5rem, line-height 1.25, tracking -0.025em): Page titles ("Invoices", "Client: Acme Corp"). The `.text-page-title` utility.
- **Title** (600, 18px / 1.125rem, line-height 1.375): Card titles, modal titles, section headers within a page. The `.text-card-title` utility.
- **Body** (400, 15px / 0.9375rem, line-height 1.625): Default body text. Generous 1.625 leading for comfortable reading of invoice notes, descriptions, and form help text. Max line length 65–75ch on long-form content.
- **Label** (600, 12px / 0.75rem, line-height 1.5, tracking 0.05em, uppercase): Table headers, small section labels, badge text. The `.text-table-header` utility.
- **Mono** (400, 13px / 0.8125rem): IDs, invoice numbers, codes, API tokens. Anything that's a copyable identifier.

### Named Rules

**The Tabular Figures Rule.** Money columns, totals, dates, and invoice numbers use `font-variant-numeric: tabular-nums`. Amounts align in vertical columns without the user squinting. This applies even when the typeface is proportional everywhere else.

**The No Decoration Rule.** No gradient text. No text-shadow. No drop-shadow on headings. Emphasis through weight and size, never through effects.

**The 65ch Rule.** Long-form body text (invoice notes, descriptions, help docs) caps at 65–75 characters per line. Forms and tables are exempt; their layout drives line length.

## 4. Elevation

Subtle ambient layering. Most surfaces lift slightly off the canvas at rest — cards have a soft shadow that suggests paper on a desk. Modals and dropdowns lift further. Shadows are never theatrical; they describe physical relationships, not decoration.

### Shadow Vocabulary

- **`shadow-xs`** (`0 1px 2px 0 rgb(0 0 0 / 5%)`): The faintest lift. Buttons at rest, chips.
- **`shadow-sm`** (`0 1px 3px 0 rgb(0 0 0 / 10%), 0 1px 2px -1px rgb(0 0 0 / 10%)`): Default card resting shadow. Soft, ambient, paper-on-desk.
- **`shadow-md`** (`0 4px 6px -1px rgb(0 0 0 / 10%), 0 2px 4px -2px rgb(0 0 0 / 10%)`): Dropdowns, popovers, hovered cards.
- **`shadow-lg`** (`0 10px 15px -3px rgb(0 0 0 / 10%), 0 4px 6px -4px rgb(0 0 0 / 10%)`): Floating action elements.
- **`shadow-xl`** (`0 20px 25px -5px rgb(0 0 0 / 10%), 0 8px 10px -6px rgb(0 0 0 / 10%)`): Modals.
- **`shadow-2xl`** (`0 25px 50px -12px rgb(0 0 0 / 25%)`): Reserved for the most prominent floating layers (full-screen wizards, command palette).
- **`ring-primary`** (`0 0 0 3px rgb(46 150 58 / 15%)`): Focus ring on form fields and buttons. Soft halo, never a hard outline.

### Named Rules

**The Paper-On-Desk Rule.** Cards at rest carry `shadow-sm`. They look like sheets of paper resting on the canvas, not like floating chrome.

**The Focus Halo Rule.** Keyboard focus is always a soft 3px green halo (`ring-primary`), never a 1px hard outline. Visible from across the room, but it doesn't shout.

**The No Inner Shadow on Inputs Rule.** Form fields are flat with a 1px border. The `shadow-inner` token exists for special cases (search bars on dark surfaces) but is not used on default inputs.

## 5. Components

Soft and approachable. Generous radii, gentle shadows, restrained color. Buttons and inputs feel tactile but quiet.

### Buttons

- **Shape:** Rounded with `radius-md` (8px). Buttons are never pill-shaped except when carrying icon-only actions on dense toolbars.
- **Primary:** Trust Green background (`#2e963a`), white text, 10px × 16px padding, weight 500, `shadow-xs` at rest. Hover shifts background to `primary-hover` (`#268032`). Focus adds the green halo ring. Active uses `primary-dark` (`#1f6c29`).
- **Secondary:** Warm Amber background (`#f0a015`), white text. Same shape rules. Used for "Save & Send", non-primary actions in the same flow as primary.
- **Ghost / Tertiary:** Transparent background, `text-primary` color, 1px transparent border that becomes `border` on hover. Hover background shifts to `surface-hover`. Used for "Cancel", row-action menus, secondary nav.
- **Destructive:** Danger background (`#ef4444`), white text, used only on destructive confirmations ("Delete invoice"). Never on the primary flow.

### Chips / Badges

- **Style:** Pill-shaped (`radius-full`), tinted background + matching saturated text. 2px × 10px padding, weight 500.
- **Status variants:** `Paid` (success-light bg / success text), `Overdue` (danger-light / danger), `Pending` / `Due Soon` (warning-light / warning), `Draft` / `Sent` (info-light / info), `Cancelled` (gray-100 / text-muted).
- **Rule:** A chip never appears with color alone — it carries a text label. Color is amplification, not signal.

### Cards

- **Corner Style:** `radius-lg` (12px). Soft, paper-like.
- **Background:** `surface` (#ffffff) on the `body-bg` (#f4f6f8) canvas.
- **Shadow Strategy:** `shadow-sm` at rest. Hover does not change shadow on static informational cards; interactive cards (clickable list items) shift to `shadow-md` on hover.
- **Border:** Optional `border-light` (#f1f5f9) when the card sits adjacent to other surfaces and needs separation. Most cards omit the border and rely on shadow.
- **Internal Padding:** 24px (`space-6`). Card headers and footers get 16px × 24px (`space-4 space-6`).

### Inputs / Form Fields

- **Style:** `surface` background, 1px `border` (#e2e8f0), `radius-md` (8px), 10px × 16px padding, body typography (15px / 400). No inner shadow.
- **Focus:** Border shifts to `primary` (Trust Green), `ring-primary` halo appears around. Smooth 200ms transition.
- **Error:** Border shifts to `danger`, ring becomes `ring-danger`. Help text below the field switches to danger color with an icon prefix.
- **Disabled:** Background `gray-50`, text `text-light`, no focus treatment.
- **Labels:** `text-label` utility — `text-sm` (13px), weight 500, color `text-secondary`. Always above the field, never floating.

### Tables (Data Grid)

- **Style:** `surface` background, `border-light` row separators. No vertical column borders.
- **Header:** `text-table-header` utility — 12px uppercase weight 600, color `text-muted`, tracking 0.05em, background `gray-50`. Padding 16px × 24px.
- **Row hover:** Background shifts to `surface-hover` (#f8fafc). No transform, no shadow.
- **Money columns:** Right-aligned. The cell renders the `.money` component (see Signature Component below) and never styles the amount itself — tabular figures, color, and the neutral/paid/overdue states all come from `.money` and its modifiers.
- **Rule:** Tables never zebra-stripe. Hover is the only row distinction.

### Empty States

One shared primitive for every "nothing here yet" surface: `@SolidInvoiceDataGrid/Components/_empty_state.html.twig` (icon medallion + title + optional message + optional CTA, styled by `.datagrid-empty*`). It is the single reusable empty state — the data grid, the "no clients yet" invoice-create gate, and any future empty collection all render through it rather than reinventing markup.

- **Medallion:** 80px `gray-50` disc, `gray-300` glyph. Neutral by default — an empty list is not an error, so it never uses a status color.
- **First-run copy names the entity.** Generic "create your first item" is a missed onboarding moment. A grid supplies its own copy through `getEmptyTitle()` / `getEmptyDescription()` (base class returns the generic fallback); the four core lists — clients, invoices, quotes, payments — override it with encouraging, plain-English copy per Design Principle 2 (Reduce admin dread).
- **No dead ends.** Every first-run empty state offers the next action. Where an entity is not created directly (Payments arrive when an invoice is paid), the CTA points at the real next step (`Create an invoice`) rather than showing nothing. A filtered-to-empty result is different: it shows the generic "no results" copy and *no* create CTA.

The first-run onboarding wizard (`/onboarding`) follows the same system — Trust Green, `--swp-*` tokens, flat surfaces, ease-out motion — via `assets/scss/components/_onboarding-wizard.scss`. It is the activation surface, so it is held to the design system as strictly as any in-app screen.

### Modals

- **Corner Style:** `radius-xl` (16px). Slightly more generous than cards.
- **Background:** `surface`, `shadow-xl`, 24px padding.
- **Backdrop:** `surface-overlay` (rgb(15 23 42 / 50%)) — slate with 50% opacity, not pure black.
- **Width:** Constrained, never full-bleed. Stops at a comfortable reading width.

### Navigation

- **Style:** Top horizontal nav (Tabler default). `surface` background, `text-secondary` for inactive links, `text-primary` weight 600 for the active link with a 2px primary-green underline beneath.
- **Active company indicator:** Always visible in the top-left. Multi-tenant safety lives here.

### Signature Component: Money Display

Tabular figures, currency prefixed and dimmed, amount prominent, two decimal places when fractional. Implemented in `assets/scss/components/_money.scss`.

**Markup contract** — this exact structure, every time:

```html
<span class="money">
  <span class="money-currency">USD</span>
  <strong class="money-amount">1,234.56</strong>
</span>
```

**Class API:**

| Class | Role |
|---|---|
| `.money` | Root. Sets tabular figures, baseline alignment, and the no-wrap guarantee. Required. |
| `.money-currency` | The currency code or symbol. Dimmed to `money-currency` (`text-secondary`), and dimmed by size too under `.money--total`. |
| `.money-amount` | The numeric amount. Defaults to `money-neutral` (`text-primary`). |

**Modifiers** — applied to the `.money` root, combinable (e.g. `money money--total money--paid`):

| Modifier | Effect |
|---|---|
| *(none)* | Amount in `text-primary`. **The neutral case, and the common one** — Draft, Pending, Sent. |
| `.money--paid` | Amount in `money-paid` (`success-dark`). **Only ever valid alongside an explicit "Paid" label** (the Money-Color Rule). |
| `.money--overdue` | Amount in `money-overdue` (`danger-dark`). Same labelling requirement. |
| `.money--total` | Amount steps to weight 600 and up one size step (`money-total-scale`, `1.125em`). Currency drops to `money-currency-scale` (`0.75em`). |

**Sizing:** the component never declares a `font-size` — it inherits, so the same markup is correct inline in a table cell, in a heading, and at `.stat-value` scale in the dashboard hero tiles. Both steps are relative (`em`), so they stay proportional at every base size.

At display size `.money--total` also drops the currency code to `money-currency-scale` (`0.75em`). At a shared size the code competes with the amount for attention it has not earned: `USD 120,882.19` should read as a number carrying a unit, not as two equal words. The reduction is scoped to `--total` deliberately — at body size (a table row, an activity feed) the same step would put the code near 10px, which is a legibility problem rather than a fix, so the code stays full size there.

**Consuming contexts may cancel the step.** A container whose own type is already the emphasis should not compound it: `.stat-value` sets `.money--total .money-amount { font-size: 1em }`, keeping the weight but dropping the size step. Without that, the dashboard hero tiles rendered money at 27px inside a ~194px box and overflowed the card. `.money` is `white-space: nowrap` by design, so an amount that does not fit overflows rather than wrapping — width has to be won back in the stylesheet, never absorbed at render time.

**Rules:**

- A SolidInvoice money display is the single source of truth for any currency value. Raw `{{ amount }}` rendering without this component is prohibited.
- The currency never wraps away from its amount. A currency code orphaned onto a previous line is exactly the misreading the system exists to prevent.
- Every color the component can take is AA-compliant against both `surface` and `body-bg`. Money is never rendered in a color that only works on some backgrounds — see The Legible Status Rule.

## 6. Do's and Don'ts

### Do:

- **Do** use Trust Green (`#2e963a`) sparingly. Primary CTAs, "Paid" states, active nav, focus rings. If green covers more than ~10% of any screen, you're overusing it.
- **Do** put status meaning on color *and* label. A green chip without "Paid" text is wrong.
- **Do** use tabular figures (`font-variant-numeric: tabular-nums`) for every money column, total, and invoice ID.
- **Do** lift cards with `shadow-sm` at rest. The paper-on-desk feel is the whole point.
- **Do** keep the active company name visible at all times. Multi-tenant safety is a hard requirement.
- **Do** honor `prefers-reduced-motion` — transitions degrade to instant.
- **Do** use the `swp-*` CSS custom properties for all values. No hardcoded hex codes in component SCSS.
- **Do** keep body line length at 65–75ch for long-form content.

### Don't:

- **Don't** ship anything that looks like the legacy AdminLTE / Bootstrap admin panel — dark blue navbar, sidebar tree, boxy widgets. The rewrite exists to escape this.
- **Don't** copy enterprise-accounting density (QuickBooks, Sage, Xero). Dense corporate toolbars, 90s-feeling forms, intimidating layouts are the anti-target.
- **Don't** add purple gradients, glass cards, gradient text, hero-metric tiles, or identical card grids — the AI-slop SaaS template aesthetic. PRODUCT.md names this explicitly.
- **Don't** go neon-on-black. Crypto/fintech dark UIs with neon accents are wrong for SMB invoicing.
- **Don't** use `border-left` greater than 1px as a colored accent stripe on cards, list items, or alerts. Use background tint or full borders instead.
- **Don't** use `background-clip: text` with a gradient. Emphasis via weight or size, never gradient text.
- **Don't** use pure `#000` or `#fff` for text. Use `text-primary` (#1e293b) and surface tokens.
- **Don't** rely on color alone to signal status. Always pair with a label.
- **Don't** add bounce easings or elastic transitions. Ease-out curves only.
- **Don't** animate CSS layout properties (width, height, top, left). Animate `transform` and `opacity`.
- **Don't** wrap solitary content in a card just because it's there. Cards are an affordance, not a default.
- **Don't** zebra-stripe tables. Hover state is the only row distinction.
- **Don't** introduce new font families. System fonts only.
- **Don't** use em dashes in UI copy. Use commas, colons, semicolons, periods, or parentheses.
