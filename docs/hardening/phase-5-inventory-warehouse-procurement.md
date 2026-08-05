# Express Cloud Hardening Phase 5 — Inventory, Warehouse and Procurement Expansion

Phase 5 introduces warehouses as operational stock locations distinct from
branches while retaining branch ownership and authorization. The existing
branch stock table remains a compatibility aggregate; warehouse balances and
append-only movements become the detailed operational source.

## Delivered inventory and warehouse capabilities

- Warehouses with branch ownership, default-location semantics, operational
  type, sales/receipt capability and active state.
- Units of measure and product purchase, sales and base-unit assignments.
- Product variants, batches, expiry metadata and serial-number records.
- Warehouse balances segmented by product, variant, batch and stock condition.
- Available, reserved, quarantined and damaged stock controls.
- Weighted-average cost and inventory-value tracking on every warehouse balance.
- Stable ordered row locks for opposite-direction warehouse transfers.
- Reservations, releases, physical counts, count variances and condition
  transfers, each represented in the append-only stock movement ledger.
- Reorder rules and valuation snapshots.
- Projection of legacy branch movements into the default warehouse without
  duplicating movements already created by the warehouse ledger.

## Delivered procurement capabilities

- Purchase requisitions, approval, conversion to purchase orders and branch/
  warehouse scope enforcement.
- Partial receipts, accepted and quarantined quantities, backorder calculation
  and receipt history.
- Landed-cost allocation and weighted-average inventory capitalization.
- Supplier credit notes and applications.
- Existing supplier bill/payment paths remain integrated with the Phase 3
  financial posting coordinator.
- Every retryable write uses the Phase 2 command boundary and operation record.

## Invariants

1. Every stock-changing command writes one append-only movement per balance
   effect; transfers and condition changes write a correlated out/in pair.
2. Warehouse balance quantity, reserved quantity, weighted-average cost and
   inventory value update inside one database transaction.
3. Available stock cannot become negative and reserved stock cannot exceed the
   on-hand balance.
4. Transfer and multi-balance locks use deterministic ordering.
5. Physical counts preserve the system quantity, counted quantity and variance.
6. Partial receipts cannot exceed the remaining purchase-order quantity.
7. Landed costs cannot be capitalized into an empty balance.
8. Financial inventory variances continue through the Phase 3 journal contract.

## Exit gate

- Full test suite passes.
- Fresh migration and seed pass.
- Warehouse runtime tests prove quantity and value conservation.
- Reservation and count tests prove append-only movement coverage.
- Procurement source contracts prove partial receipt, backorder and landed-cost
  paths.
- `inventory:valuation-snapshot` reports no negative balances or reservation
  overruns.
