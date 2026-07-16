# Supplier Finance

Sprint 11 adds supplier bills, bill payments, supplier returns, supplier
documents, and outstanding supplier-balance reporting.

## Supplier bills

Bills may be linked to a purchase order but can also represent non-stock
supplier expenses.

Each bill stores:

- internal bill number;
- optional supplier invoice reference;
- supplier and branch;
- optional purchase order;
- bill and due dates;
- line quantities, costs, tax, and totals;
- paid amount and derived status;
- mandatory reference note;
- optional supporting document.

Bill lines support tracked products and non-product expense descriptions.

## Payments

Payments:

- require an active payment method;
- run under a row lock;
- reject overpayment;
- update paid amount and status atomically;
- preserve each individual payment row;
- require audit logging.

## Supplier returns

Confirmed returns:

- require a supplier, branch, reason, and reference note;
- apply only to tracked products;
- lock branch stock before subtraction;
- reject quantities above available stock;
- create immutable inventory return movements;
- refresh low-stock alerts;
- preserve return lines and total cost.

## Documents

Documents are stored outside the public web root.

Download requires permission and creates an audit event. Accepted release
formats are PDF, common images, XLSX, and DOCX.

## Supplier balances

The report totals open and partially paid bills only. It uses a supplier
subquery and indexed bill-status fields rather than loading every bill into
application memory.
