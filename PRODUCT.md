# Product

## Register

product

## Users

Freelancers through small business owners (1–20 people) using SolidInvoice to manage clients, quotes, invoices, and payments. The spectrum spans solo operators billing between project work and non-technical SMB owners doing admin in the evenings, plus the occasional bookkeeper running it as a daily tool. Context is mostly desktop with occasional mobile checks; the user is rarely an accounting expert and often arrives reluctantly because the task is "boring but important."

The job to be done: get paid faster, with less friction, and look professional doing it. On any given screen the primary task is usually creating, sending, or reconciling money — not exploring the app.

## Product Purpose

Open-source, self-hostable invoicing for people who don't want enterprise accounting software. SolidInvoice exists so a freelancer or small business can run their billing without surrendering data to a SaaS vendor or paying per-seat to QuickBooks/Xero. Success looks like: an invoice goes out in under a minute, a payment is recorded without ambiguity, and the user trusts what the totals say without double-checking in a spreadsheet.

The current rewrite (AdminLTE → Tabler / Bootstrap 5.3) is the moment to shed the legacy "admin panel" feel and become a modern product UI that's pleasant to live in.

## Positioning

Get paid faster, and own everything. The invoice goes out in under a minute and the data stays yours — self-hosting is the how, not the headline. Competitors ask users to choose between speed and control: SaaS billing tools are quick but hold the data hostage, while self-hosted alternatives hand back control and charge for it in friction. SolidInvoice refuses the trade.

Every screen reinforces this by serving the money task first and never making ownership feel like a tax. If a screen is slower, rougher, or more manual *because* the app is self-hosted, the positioning has been broken — independence is supposed to be invisible in the daily flow, not something the user pays for on every click.

## Brand Personality

Friendly, approachable, modern. The app should feel like a competent colleague, not an accountant's office. Voice is plain English, never jargon-heavy; tone is reassuring around money ("Paid", "Sent", "Due in 3 days") and never scolding around mistakes. Emotionally the goal is *relief* — the user expected admin pain and got a clean, quiet tool instead.

## Anti-references

- **Legacy AdminLTE / Bootstrap admin dashboards.** Dark blue navbar, sidebar tree, boxy widgets, generic icon set. The exact look the rewrite is escaping.
- **Enterprise accounting (QuickBooks, Sage, Xero).** Dense corporate UIs, cluttered toolbars, 90s-feeling forms, intimidating density. SolidInvoice is the opposite end of the market.
- **AI-slop SaaS templates.** Purple gradients, glass cards, hero-metric tiles, identical card grids, gradient text. Generic startup-dashboard aesthetic.
- **Crypto / fintech neon-on-black.** Aggressive dark UIs with neon accents. Wrong register entirely for SMB billing.

## Design Principles

1. **Money clarity first.** Every amount, currency, and total is unambiguous. Numbers are aligned, formatted with explicit currency, and never truncated or hidden behind a hover. If a value could be misread, the design is wrong — not the user.
2. **Reduce admin dread.** The app should feel lighter than the task. Empty states are encouraging, language is soft and human, errors explain rather than scold. The user came in expecting friction; remove it.
3. **Multi-tenant safety.** The active company is always visible and unambiguous. Switching context is deliberate, never accidental. No screen ever lets a user act on the wrong tenant's data — the UI makes the boundary obvious before the backend has to.
4. **Modern without flash.** Tabler-era patterns done with restraint — clean typography, generous whitespace, purposeful color — but no decorative motion, no gradient maximalism, no trend-chasing. The look should still feel right in five years.

## Accessibility & Inclusion

Target WCAG 2.1 AA across the application. Practical implications:

- Contrast ratios meet AA for all text and meaningful UI elements, including secondary text and disabled states where they convey information.
- Full keyboard navigation with visible focus rings; no mouse-only interactions for core flows (create, send, record payment).
- Honor `prefers-reduced-motion` — transitions degrade to instant or near-instant.
- Form fields have programmatic labels, and validation errors are announced, not just colored.
- Color is never the sole carrier of meaning (e.g. invoice status uses label + color, not color alone).
- Numeric content uses tabular figures so amounts align and scan cleanly.
