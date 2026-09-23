# E-invoicing conformance corpora

The e-invoicing conformance suite runs against three official corpora: the EN 16931 validation
artefacts from CEN TC434, the Peppol BIS Billing 3.0 rule files, and the KoSIT XRechnung test suite.

None of the three is committed here. `corpus.lock.json` pins each one to an immutable ref, and
`scripts/fetch-einvoicing-corpus.php` downloads it and checks the pin. Read
[`CORPUS-LICENCES.md`](CORPUS-LICENCES.md) for the licence of each corpus and why we do not commit it.

## Files

| File | What it is |
|---|---|
| `corpus.lock.json` | The version pin. One entry per corpus. This is the file you edit to move to a new version. |
| `corpus.lock.schema.json` | The JSON Schema for `corpus.lock.json`. `CorpusIntegrityTest` validates the lock file against it. |
| `CORPUS-LICENCES.md` | The licence record, and the date the licences were last checked. |
| `corpus/` | Where the fetcher writes the payload. Git ignores everything in it. |

## Fetch a corpus

```bash
composer conformance:fetch
```

The fetcher is idempotent. It writes a `.fetched.json` stamp next to each payload, and it does
nothing for a corpus whose stamp already matches the pin in `corpus.lock.json`.

| Command | What it does |
|---|---|
| `composer conformance:fetch` | Fetches every corpus that is missing or stale. |
| `composer conformance:fetch -- --force` | Fetches every corpus again, even when the stamp matches. |
| `composer conformance:fetch -- --check` | Exits non-zero if any corpus is missing or stale. Prints nothing when all are current. |

## Run the conformance suite

```bash
composer conformance
```

This fetches the corpora, then runs the `conformance` test group. Until the validation engine lands,
the suite reports every corpus document as unimplemented rather than as passing. That is the intended
behaviour, not a failure.

## How the pin is checked

- **`kind: "git"`** — the fetcher does a shallow fetch of `refs/tags/<ref>`, then reads
  `git rev-parse refs/tags/<ref>`. The result must equal `commit`. A moved tag is a hard failure, not
  a warning.
- **`kind: "zip"`** — the fetcher downloads the asset, then compares `hash_file('sha256', ...)` against
  `sha256`. It checks the hash before it extracts a single byte.

The fetcher names the tag ref in full, and you must too when you read a pin. `eInvoicing-EN16931`
carries a branch **and** a tag called `validation-1.3.16`, and they point at different commits.
`git clone --branch validation-1.3.16` gives you the branch, which is mutable, so the pin would not
hold from one day to the next.

After the pin is verified, the fetcher keeps only the directories listed in `paths` and discards the
rest, including the `.git` directory of a cloned corpus. A path in `paths` that does not exist
upstream is a hard failure, because it means the corpus was reorganised.

## Move a corpus to a new version

1. Find the new tag or release upstream.
2. Edit the corpus entry in `corpus.lock.json`:
   - change `ref`;
   - for `kind: "git"`, change `commit` to the commit the new tag points at. Read it from
     `git ls-remote <url> 'refs/tags/<new-ref>'`, and take the `refs/tags/` line. Do not take a
     `refs/heads/` line, and do not take a `^{}` line.
   - for `kind: "zip"`, change `url` to the new asset and `sha256` to the hash of that asset
     (`sha256sum <file>`).
3. Run `composer conformance:fetch -- --force`.
4. Run `composer conformance` and read the diff in the report. A change in rule counts or outcomes is
   the deliverable of the bump, not noise. Put it in the pull request description.
5. Re-check the upstream licence. If it changed, update
   [`CORPUS-LICENCES.md`](CORPUS-LICENCES.md) before you merge.

## Requirements

The fetcher needs `git` on the path and the `zip` PHP extension. It uses no Symfony container and no
extra Composer package.
