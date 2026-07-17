# Procurement and Low-Stock Operations

Sprint 8 adds purchase orders and goods receipt on top of the Sprint 7 stock
ledger.

## Procurement

- Purchase orders belong to one supplier and receiving branch.
- Lines store ordered and received quantities separately.
- Draft orders require explicit approval.
- Approved and partially received orders may receive goods.
- Every received tracked item uses the same stock-intake service as manual
  intake.
- Receiving more than the outstanding quantity is rejected.
- Goods receipt and inventory movement occur in one transaction.

## Low stock

Every balance change refreshes the low-stock state.

- One open alert exists per product and branch.
- Alerts update while stock remains low.
- Alerts resolve automatically when stock rises above the minimum.
- Untracked products never enter this workflow.
- The report uses cursor pagination and indexed open-alert queries.
