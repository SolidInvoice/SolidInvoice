# Architecture decision records

This directory holds the accepted architecture decisions for SolidInvoice. Each record
explains one decision, why it was taken, and what it rules out. It is engineering
material, not end-user documentation — the user manual lives in `docs/`.

Files are numbered in order of acceptance, and the number never changes. **The decision text of an
accepted record is never rewritten.** If a decision changes wholesale, write a new record that
supersedes the old one, and add a `Superseded by` line to the old record.

Exactly two edits to an accepted record are permitted:

1. A `Superseded by` line, as above.
2. An entry appended to an **`Amendments`** section at the foot of the record, together with the
   one-line `**Amendments:**` pointer in the record's header block. An amendment carries a pointer
   and nothing more — which section it corrects, one sentence on what changed, and where the
   reasoning lives. It never restates, rewrites or deletes decision text, and entries are only ever
   appended.

The second rule exists because a record that stays binding for months is read by people who will
never see the thread that corrected it. A correction discoverable only from somewhere else is a
correction that does not arrive.

| ADR | Title | Status |
|---|---|---|
| [0001](0001-einvoicebundle-architecture-and-interfaces.md) | `EInvoiceBundle` architecture and interfaces | Accepted |
