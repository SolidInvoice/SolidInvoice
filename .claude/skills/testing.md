# Testing

**Moved. See [`AGENTS.md`](../../AGENTS.md) — §13 (testing expectations) and §2 (test
commands and the test database).**

This file restated those conventions and had gone stale against them. It presented
`bin/phpunit` as the way to run the suite, where CI runs `bin/paratest`, and it omitted
the parts that actually catch people out:

- Installation/Panther tests are excluded by default and must not be run under paratest —
  `AGENTS.md` §2 explains why.
- `.env.test` pins `FOUNDRY_FAKER_SEED`, so faker output is deterministic; a test that
  passes on a lucky random value fails for everyone else.
- There is deliberately **no** coverage threshold. Do not invent one.
- `db-tests.yml` re-runs the suite across 14 database versions — the long pole on any
  schema change.
