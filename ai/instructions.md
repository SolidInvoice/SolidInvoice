# AI instructions — SolidInvoice

**Read [`AGENTS.md`](../AGENTS.md). It is the source of truth for this repository.**

Everything an agent needs — commands, house style, testing expectations, migrations,
money/tax rules, translations, PR conventions and the standing no-merge rule — is there,
and only there.

This file used to be a byte-identical copy of `GEMINI.md`, which was itself a copy of
`AGENTS.md` — three copies of the same 1600 lines, all declaring `**Current Version:**
2.3.11` against a `composer.json` of `3.0.1`. Keeping copies in sync by hand does not
work, so there is no longer a copy.

If you are about to add a convention here, add it to `AGENTS.md` instead. If `AGENTS.md`
is wrong, fix `AGENTS.md`.
