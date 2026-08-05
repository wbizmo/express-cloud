# Express Cloud Hardening Phase 4 — Enterprise Accounting Suite

Phase 4 extends the Phase 3 immediate-posting backbone. It does not replace the
atomic source-to-journal contract and does not reintroduce deferred accounting
as the primary posting path.

## Delivered accounting controls

- Expanded account classification with group, normal-balance, reporting,
  cash-flow, subledger and tax metadata.
- General, cash, bank, clearing, sales, purchase, inventory and adjustment book
  classification on posted journals.
- Immutable close batches that record the reconciliation evidence used to lock
  an accounting period.
- Locked-period enforcement and journal linkage to the close batch that locked
  it.
- Customer and supplier control-account reconciliation with aged receivable and
  payable schedules.
- Cash book, bank book, tax ledger, inventory valuation, trial balance, profit
  and loss, balance sheet and cash-flow statements.
- Bank-account statement imports, exact debit/credit direction matching,
  partial matching and zero-difference finalization.
- Treasury accounts and balanced cash/bank/clearing transfers through the Phase
  2 idempotent command boundary.
- Accrual and prepayment schedules with unique period postings.
- Fixed-asset disposal with gain/loss posting and retained immutable source
  history.
- Cash-counter and treasury movement records for later POS shift integration.
- `accounting:enterprise-audit --fail-on-gap` as a read-only enterprise control
  gate, alongside the Phase 3 `accounting:reconcile` repair scanner.

## Invariants

1. Source posting remains atomic and idempotent.
2. Posted journals remain balanced and immutable.
3. A locked period cannot accept new postings or silent edits.
4. A period cannot close while source posting, control accounts, bank
   statements, inventory valuation or the balance sheet have a difference.
5. Bank reconciliation cannot finalize with unmatched lines or an opening/
   movement/closing balance difference.
6. Customer and supplier subledgers must tie to their ledger control accounts.
7. Corrections use reversal or a new source transaction; posted history is not
   overwritten.

## Exit gate

- Full test suite passes.
- PHPStan does not exceed the Phase 3 budget.
- Fresh migrations and seeders pass.
- `accounting:reconcile --repair --fail-on-gap` reports zero differences.
- `accounting:enterprise-audit --fail-on-gap` reports zero enterprise-control
  differences.
- Financial statements and control accounts tie on the isolated smoke database.
