# Product Catalogue, Suppliers, and Pricing

## Product identity

Each product has:

- name;
- unique SKU;
- optional barcode;
- required managed category;
- optional brand;
- optional image;
- optional description;
- active or inactive status.

Barcode scanners are supported through keyboard-emulation input.

## Inventory behaviour

`track_inventory` is the authoritative product behaviour flag.

When true, the product participates in branch stock, stock validation,
movements, and low-stock reporting.

When false, the product remains invoiceable but:

- has no stock quantity;
- never generates a stock movement;
- is never blocked by stock availability;
- never appears in low-stock reporting;
- displays an em dash wherever quantity is not applicable.

Examples include delivery fees, framing labour, repair labour, and gift
wrapping.

## Product editing boundary

Product identity and pricing changes must never delete, rewrite, or otherwise
mutate stock movements, sales history, or audit history.

Opening stock and later stock intake are separate inventory actions.

## Pricing

Every product has one default selling price.

A branch-specific price row exists only where a branch genuinely needs an
override. Price resolution is:

1. branch override;
2. product default price.

Money is stored as integer kobo.

## Suppliers

Supplier records support:

- supplier code;
- company and contact details;
- category;
- encrypted email;
- encrypted tax number;
- payment terms;
- credit limit;
- lead time;
- delivery terms;
- return policy;
- preferred-supplier status;
- notes.

Supplier purchasing, performance, ledger, price history, and returns are
implemented in procurement sprints.

## Imports

The Excel-only import workflow and downloadable sample workbook are
implemented in Sprint 6. Sprint 5 establishes the validated destination
models and fields first.
