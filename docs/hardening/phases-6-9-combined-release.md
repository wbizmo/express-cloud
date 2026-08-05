# Combined Phases 6-9 release

This release is deliberately atomic. Sales/CRM, POS, HR/administration and pagination/performance are published together because POS depends on the canonical sales engine, administration controls the reference data used by both, and Phase 9 defines the safe loading contract for every new surface.

A failure in any phase rolls back the complete worktree and prevents the branch/tag push.
