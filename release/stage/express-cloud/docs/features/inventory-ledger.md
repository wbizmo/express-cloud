# Inventory Ledger

Sprint 7 introduces per-branch stock balances and an immutable stock movement
ledger.

## Rules

- Only products with `track_inventory = true` may create stock records.
- Product editing never mutates balances or stock history.
- Every stock change writes one immutable movement row.
- Transfers write two linked rows in one transaction.
- Current balance and the movement ledger update atomically.
- Source balances are locked before subtraction.
- Negative balances are rejected.
- Adjustments require a reason and reference note.
- Quantities support three decimal places through integer milliunits.
- Per-branch minimum stock is stored with the branch balance.
