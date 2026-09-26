# Conformance corpus licences

SolidInvoice is MIT licensed. The three conformance corpora are not. This file records the licence
of each corpus, and the decision that follows from it.

**No corpus file is committed to this repository; all three are fetched at the pinned ref recorded in
`corpus.lock.json`.**

**Licences last checked: 2026-09-22.**

## The corpora

| Corpus | Source repository | Pinned ref | SPDX licence | Licence at that ref | Committed here? |
|---|---|---|---|---|---|
| EN 16931 validation artefacts (CEN TC434) | [ConnectingEurope/eInvoicing-EN16931](https://github.com/ConnectingEurope/eInvoicing-EN16931) | `validation-1.3.16` | `EUPL-1.2` | [LICENSE.txt](https://github.com/ConnectingEurope/eInvoicing-EN16931/blob/validation-1.3.16/LICENSE.txt) | No |
| Peppol BIS Billing 3.0 | [OpenPEPPOL/peppol-bis-invoice-3](https://github.com/OpenPEPPOL/peppol-bis-invoice-3) | `v3.0.20` | `UNLICENSED` | [no licence file at the ref](https://github.com/OpenPEPPOL/peppol-bis-invoice-3/tree/v3.0.20) | No |
| XRechnung test suite (KoSIT) | [itplr-kosit/xrechnung-testsuite](https://github.com/itplr-kosit/xrechnung-testsuite) | `v2026-08-31` | `Apache-2.0` | [LICENSE](https://github.com/itplr-kosit/xrechnung-testsuite/blob/v2026-08-31/LICENSE) | No |

## Why nothing is committed

- **EN 16931 is EUPL-1.2.** The EUPL is a reciprocal copyleft licence. Every corpus file also carries
  the notice in its own XML header. Copying those files into an MIT tree raises a derivative works
  question. We do not have to answer it, so we do not create it.
- **Peppol BIS Billing 3.0 carries no licence.** There is no `LICENSE` file, no `COPYING` file, and no
  licence statement in `README.adoc` at `v3.0.20`. GitHub reports no licence for the repository.
  Without a grant, default copyright applies, and we may not redistribute the files.
- **The XRechnung test suite is Apache-2.0**, so we could commit it with attribution. We do not.
  One rule for all three corpora is easier to keep correct than three different rules.

Fetching a published conformance corpus to run tests against is use, not redistribution. None of the
three positions above limit that use. Redistribution is what raises the question, so we do not
redistribute.

## Where the files go

The fetcher writes each corpus to `corpus/<id>/`. That directory holds a `.gitignore` with `*` and
`!.gitignore`, so git never tracks a fetched file. See [`README.md`](README.md) for how to fetch a
corpus and how to move one to a new version.

## Re-checking a licence

Re-check the upstream licence every time you change a `ref` in `corpus.lock.json`. Step 5 of the
update procedure in [`README.md`](README.md) says the same thing. If the licence changed, update the
table above and the "last checked" date before you merge the version bump.
