# Copilot instructions — SolidInvoice

**Read [`AGENTS.md`](../AGENTS.md). It is the source of truth for this repository.**

Everything Copilot needs — commands, house style, testing expectations, migrations,
money/tax rules, translations, PR conventions and the standing no-merge rule — is there,
and only there.

This file used to be a 1611-line copy of `AGENTS.md`. It drifted, and not harmlessly: as
well as declaring `**Current Version:** 2.3.11` against a `composer.json` of `3.0.1`, it
had lost the repository's "backed enums, never class constants for fixed sets of values"
rule and still advised the opposite. Anyone reading only this file was being told to write
code the codebase rejects. Keeping a copy in sync by hand does not work, so there is no
longer a copy.

If you are about to add a convention here, add it to `AGENTS.md` instead. If `AGENTS.md`
is wrong, fix `AGENTS.md`.
