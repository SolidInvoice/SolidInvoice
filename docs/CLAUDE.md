# CLAUDE.md — Documentation

Scope: end-user documentation for SolidInvoice, synced with GitBook. This file layers on top of the project root `CLAUDE.md` (which covers code). When editing files under `docs/`, follow this guide.

## Audience & purpose

These docs are for **end users** of SolidInvoice — freelancers, small business owners, and self-hosters — not contributors or API consumers.

- Developer/contributor guidance lives in the project root (`README.md`, `CONTRIBUTING.md`, root `CLAUDE.md`).
- API reference is auto-generated and published at `https://solidinvoice.app/api/docs` — do not duplicate it here.

## GitBook sync

This directory is mirrored to GitBook. Commits prefixed `GITBOOK-N: ...` are pushed *from* GitBook back into git — they are authoritative for content edited in the GitBook UI.

- Do not reformat or restructure files wholesale; the sync will conflict.
- GitBook-specific syntax is allowed and expected:
  - `{% hint style="info" %} ... {% endhint %}` (also `warning`, `danger`, `success`)
  - `<figure><img src="..." alt=""><figcaption><p>...</p></figcaption></figure>` for images
  - `{% tabs %}` / `{% code %}` blocks
- Keep the existing dialect when editing a page. Don't convert hint blocks to plain blockquotes or vice versa.

## Structure rules

**Every new page must be registered in `SUMMARY.md`** — GitBook builds navigation from it. Pages not listed will not appear in the sidebar.

Conventions in `SUMMARY.md`:
- Top-level entries are flat bullets at the top (e.g. `Installation Guide`, `Cron Job Setup`).
- Grouped sections use `## Heading` with optional leading emoji (e.g. `## 🏢 Companies`). Match the style of existing sections when adding a new one.
- Each section's pages live in a topic subdirectory: `companies/`, `managing-clients/`, `integrations/`, `installation-guide/`. Create a new subdirectory for a new section rather than adding loose top-level files.
- Section index pages are named `README.md` inside the subdirectory (e.g. `installation-guide/README.md`), linked as `[Installation Guide](installation-guide/README.md)`.

## Assets

- All images go in `.gitbook/assets/` (one flat folder, no subdirs — that's the GitBook convention).
- Reference from a page with a relative path: `../.gitbook/assets/filename.png`.
- Wrap images in the `<figure>` / `<figcaption>` pattern shown in existing pages so captions render correctly in GitBook.
- Filenames may contain spaces (GitBook generates them) — don't rename existing assets just for tidiness; you'll break references.

## Voice & style

- Second person ("you"), task-oriented page titles ("Creating a company", "Switching between companies").
- Page opens with a one-sentence purpose, then jumps into steps. No marketing intros.
- Use `### Subheadings` for procedural steps within a page (top-level `#` is the page title).
- Inline UI elements in backticks: `+ Add Company`, `Create` button.
- Use hint blocks for side notes / cautions, not bold inline warnings.

## Versioning

The current development branch is `3.0.x`. Some pages and the root README still link to `2.4.x` resources. When updating a page, check version-specific URLs (CONTRIBUTING link, install instructions, screenshots showing UI that has changed in 3.0). Flag mismatches rather than silently rewriting.

## Out of scope

This directory is for user-facing documentation only. Internal planning docs, TODOs, and engineering notes live in the root `todo/` directory, not here. Do not add scratch files, plans, or working notes under `docs/`.

## Checklist for a new page

1. Create the file under the appropriate topic subdirectory.
2. Add a `# Page Title` and a one-line purpose statement.
3. Add an entry in `SUMMARY.md` under the correct section.
4. Place any images in `.gitbook/assets/` and reference them via `../.gitbook/assets/...` inside `<figure>`.
5. Verify links to other docs are relative and point at existing files.
