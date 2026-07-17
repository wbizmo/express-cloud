# Commercial Controls

Sprint 15 adds the missing commercial workflows without replacing the
existing sales, inventory, procurement, or payment foundations.

## Credit and outstanding sales

The existing sales engine already supports zero, partial, and full payment.
A sale with an unpaid balance remains confirmed or partial, and the customer
receivables screens derive what each customer owes directly from sales minus
recorded payments. Payments are appended through the existing
`AddSalePayment` action.

Staff access is creator-scoped unless the account has `sales.view.all`.
Administrators and authorized managers can view all sales.

## Sale returns

Returns must reference an original sale and original sale items. Quantities
cannot exceed the remaining unreturned quantity. Tracked items optionally
return to stock through append-only `return` stock movements. Every return is
audited.

## Discount vouchers

Vouchers are reusable and are not tied to one customer. They support fixed or
percentage values, minimum sale amount, maximum discount cap, validity dates,
usage limits, activation state, redemption logging, and one redemption per
sale.

## Purchases

Direct purchase recording references an existing supplier, branch, and only
products already present in the catalogue. Tracked products use the existing
stock-ledger intake path, so purchase recording and stock intake cannot drift.

Existing purchase orders and goods receipt remain available for planned
procurement.

## Stock transfers

The existing `StockLedger::transfer()` implementation remains canonical. It
writes linked transfer-out and transfer-in movements in one transaction and
updates both branch balances atomically.

## Enterprise controls

All ordinary selects are alphabetically sorted in the browser unless marked
`data-no-sort`. Checkboxes are progressively enhanced into animated toggles.
Radio-style choices have card controls, and page headers can receive a
consistent back button through `data-page-header`.
