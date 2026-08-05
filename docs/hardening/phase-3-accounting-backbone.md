# Express Cloud Phase 3 Immediate Accounting Backbone

Phase 3 moves accounting from a manual-first repair command into the source transaction.

## Posting contract

Every financial source event must have exactly one row in `financial_postings`:

- `posted` with one balanced source journal; or
- `non_posting` with an explicit reason and no journal.

The unique source tuple is `source_type + source_id + source_event`. The database also prevents one journal from being claimed by multiple posting records.

## Immediate source coverage

- Confirmed invoices and POS sales, including tax and inventory cost.
- Split and partial sale payments.
- Credit sales through accounts receivable.
- Returns and voids, including tax reversal and restocked cost reversal.
- Reissues through the existing void-and-create workflow.
- Purchase receipts.
- Supplier bills, payments and return credits.
- Purchase returns.
- Standalone receipts and payment-method ledgers.
- Fixed-asset acquisition and monthly depreciation.
- Positive and negative stock variances.
- Manual, batch, reversal and opening-balance journals.

Quotes, transfers, source-derived stock movements, drafts and zero-value operations are explicitly classified as non-posting.

## Repair path

`php artisan accounting:reconcile --repair --fail-on-gap` scans historical source records idempotently, repairs missing posting records or journals and fails when any source remains unclassified or any posted journal is unbalanced.

`accounting:sync` remains as a deprecated alias for operational compatibility.

## Deferred to Phase 4

Phase 4 expands the chart, subledgers, tax detail, bank reconciliation, cash books, valuation methods, close controls and financial statements. Phase 3 provides the atomic source-to-journal contract those features depend on.
