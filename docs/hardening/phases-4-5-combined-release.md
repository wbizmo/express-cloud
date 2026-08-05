# Express Cloud Hardening Phases 4 and 5 — Combined Release Contract

Phases 4 and 5 are delivered by one all-or-nothing runner because warehouse
valuation, procurement receipts, landed cost, stock variances and enterprise
financial statements cross both domains. They remain separately documented and
separately gated.

The runner is anchored to the exact Phase 3 commit. It creates an external
rollback capsule before mutation, applies a checksum-verified Perl payload,
runs both domains' gates, commits once, creates one annotated combined tag and
atomically pushes the branch and tag. Any accounting or inventory/procurement
failure restores the Phase 3 branch, HEAD, index, tracked, untracked and ignored
state. No partial Phase 4 or Phase 5 publication is permitted.

## Combined publication gate

- Phase 4 accounting gate passes.
- Phase 5 warehouse/procurement gate passes.
- Full PHPUnit, Pint, Vite and PHP syntax gates pass.
- PHPStan is no higher than the Phase 3 budget of 145 findings.
- Fresh migration and production seed pass on isolated SQLite.
- Source-to-ledger reconciliation is zero-difference.
- Enterprise accounting audit is zero-difference.
- Warehouse valuation audit has no negative or over-reserved balances.
- Route and command registers contain the enterprise endpoints and commands.
- Release tree contains no secret, backup source, generated release or runner
  debris.
