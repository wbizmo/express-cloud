# Express Cloud Phase 14 — Functional Workflow Closure and Visual Polish

Phase 14 closes the remaining procurement lifecycle gaps before visual refinement.

## Functional closure

- One canonical purchase-order receipt path; duplicate stock intake removed.
- Draft and unreceived approved purchase orders can be revised.
- Revising an approved order resets approval and returns it to draft.
- Draft and unreceived approved orders can be cancelled without deleting history.
- Partially received orders can cancel only their outstanding quantity.
- Goods receipts can be voided through compensating stock and accounting entries after active landed costs are reversed.
- Landed-cost allocations can be reversed without deleting the original allocation or journal.
- Branch and warehouse transfer behaviour remains atomic and append-only.
- Runtime and source-contract tests cover lifecycle and transfer invariants.

## Visual system

- Material Symbols Outlined is the application icon system.
- User-facing emoji characters are prohibited by an automated test.
- Shared cards, buttons, form controls, record panels, empty states and responsive layouts receive a consistent Express Cloud treatment.
- Red is reserved for destructive actions and high-risk status; navy and blue lead the interface.
