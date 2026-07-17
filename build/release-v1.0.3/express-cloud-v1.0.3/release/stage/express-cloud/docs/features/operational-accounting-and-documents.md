# Operational Accounting and Documents

Sprint 18 adds the transaction records that must exist before the full
QuickBooks-level ledger is introduced in Sprint 19.

## Standalone receipts

Money can be received without creating an invoice or sale. The receipt records
the branch, optional customer, payer, payment method, amount, reference,
purpose, notes, receiving staff member, and exact time. It does not affect
stock and does not falsely create revenue or an invoice.

Sprint 19 will map these receipts to cash/bank accounts, customer deposits,
income, liabilities, or other selected ledger accounts.

## Purchase returns

Purchase returns must reference an existing purchase receipt and its original
lines. Returned quantities cannot exceed the unreturned quantity received.
Tracked branch stock is reduced and an append-only `purchase_return` stock
movement is recorded.

## Sale returns

The existing Sprint 15 sale-return engine remains canonical. Sprint 18 adds
consistent PDF and spreadsheet downloads for sale returns.

## Fixed assets

The asset register stores acquisition cost, salvage value, useful life,
location, branch, custodian, serial number, and status. Straight-line monthly
depreciation is calculated for operational visibility.

Sprint 19 will add asset accounts, accumulated depreciation, depreciation
expense journals, disposals, write-offs, and financial-statement integration.

## Company logo and document branding

Administrators can upload, replace, or remove the company logo. The logo is
optional and every PDF omits the image cleanly when no logo exists. Product
records remain image-free.

## Operation documents

PDF and spreadsheet downloads are available for:

- standalone receipts;
- purchase receipts;
- purchase returns;
- sale returns;
- stock transfers and any stock action sharing a movement reference;
- fixed-asset records.

Every generated document receives a SHA-256 hash and a generation log.
PDF rendering makes no external network request.
