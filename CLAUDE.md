# CLAUDE.md — SolidInvoice

**Read [`AGENTS.md`](AGENTS.md). It is the source of truth for this repository.**

Everything Claude needs — commands, house style, testing expectations, migrations,
money/tax rules, translations, PR conventions and the standing no-merge rule — is there,
and only there.

This file used to restate those conventions in short form, and had drifted from them: it
declared Symfony 7.1+ against a `composer.json` of `^8.1`, gave a migration naming example
(`Version203011.php`) that does not match the files in `migrations/`, pointed at
`bin/phpstan analyse` rather than the `-c phpstan.test.neon` invocation CI actually runs,
and illustrated money as `Money::USD(1000) // $10.00 (cents)` — the fixed-100 assumption
that `src/MoneyBundle/Currency/CurrencyScale.php` exists to prevent. See `AGENTS.md` §2,
§9 and §10 for the current rules.

## Claude-specific material in this repo

These are tool-specific and carry content of their own. They do not restate conventions;
where they touch one, they link to `AGENTS.md`.

- [`.claude/skills/design-system.md`](.claude/skills/design-system.md) — design tokens,
  button/form/page patterns, accessibility checklist. More detail than `AGENTS.md` §7.
- [`.claude/skills/solidinvoice-feature-docs/SKILL.md`](.claude/skills/solidinvoice-feature-docs/SKILL.md)
  — writing end-user documentation for the Docusaurus site.
- [`docs/CLAUDE.md`](docs/CLAUDE.md) — Docusaurus conventions for everything under `docs/`.

`.claude/skills/code-quality.md` and `.claude/skills/testing.md` are now pointers; their
content lives in `AGENTS.md` §3/§4 and §13 respectively.

If you are about to add a convention here, add it to `AGENTS.md` instead. If `AGENTS.md`
is wrong, fix `AGENTS.md`.
