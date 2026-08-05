# Phase 6 — Sales, CRM and commercial workflows

- One canonical sales engine delegates all document creation to the Phase 2 idempotent `CreateSale` boundary.
- `SaleType` now supports quotations, sales orders, invoices and POS documents.
- Quote → order/invoice and order → invoice conversions are idempotent and append document history.
- Orders and quotations are explicitly non-financial until conversion.
- Customer groups, payment terms, credit fields, delivery records, rounding, fulfilment state and approval metadata are persisted.
- Exports stream by cursor rather than loading complete datasets.
