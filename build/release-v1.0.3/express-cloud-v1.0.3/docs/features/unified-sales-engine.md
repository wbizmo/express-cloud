# Unified Sales Engine

Sprint 10 implements invoice, quote, and POS transactions through one sales
schema and one transaction service.

## Sale types

- `invoice`: may be unpaid, partially paid, or fully paid.
- `quote`: stores lines and totals but records no payment and creates no stock
  movement.
- `pos`: uses the same engine and normally receives payment immediately.

## Idempotency

Every sale requires a client-generated idempotency key.

If the same key is submitted again, the existing sale is returned rather
than a duplicate transaction being created.

## Line snapshots

Each line captures product name, SKU, tracking behavior, quantity, price,
discount, tax, and line total. Later product edits do not rewrite sale
history.

## Stock

Tracked invoice and POS items deduct stock in the same database transaction
as sale creation.

- branch stock is locked before subtraction;
- negative balances are rejected;
- one append-only sale movement is written per tracked line;
- low-stock state is refreshed after deduction;
- untracked products remain invoiceable without stock operations;
- quotes never move stock.

## Payments

Invoices and POS sales support zero, one, or multiple payment rows.

Later payments lock the sale, reject overpayment, update the paid amount, and
derive the sale status as confirmed, partial, or paid.

## Quote conversion

A quote can be converted into an invoice or POS transaction through the same
validated request and sale-creation service. The converted sale stores its
source quote ID.
