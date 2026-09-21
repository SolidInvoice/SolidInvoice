# Code Quality & Standards

**Moved. See [`AGENTS.md`](../../AGENTS.md) — §2 (commands), §3 (baselines), §4 (house
style), §5 (Rector).**

This file restated those conventions and had gone stale against them. Two examples of why
it is no longer worth keeping as a copy:

- It listed `bin/phpstan analyse` as the static-analysis command. CI runs
  `bin/phpstan analyse -c phpstan.test.neon` after a test-env cache warmup; the bare
  invocation uses a different config and will disagree with CI. `AGENTS.md` §2.
- Its pre-commit checklist ran `bin/phpunit`. The full suite is `bin/paratest`;
  `bin/phpunit` is for a single file or filter. `AGENTS.md` §2.

It also did not mention that `phpstan-baseline.neon` is load-bearing and must not be
regenerated to make a change pass — `AGENTS.md` §3.
