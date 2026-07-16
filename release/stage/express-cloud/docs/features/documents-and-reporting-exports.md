# Documents and Reporting Exports

Sprint 13 implements the document and reporting portion of Phase 5.

## Thermal receipts

The 80mm receipt is an isolated print document with its own fixed-width print
stylesheet. It always includes the branch name and branch address. It never
loads a QR image from an external service.

## A4 invoices and quotes

The A4 document is rendered server-side with Dompdf. It includes business
branding, branch information, customer details, staff identity, itemized
lines, totals, payments, balance, notes, and an embedded verification QR code.

## Verification QR

The QR payload is a signed application URL. The PNG is generated locally and
embedded as a data URI before rendering or printing. Print-time rendering
makes zero external HTTP requests.

## Product labels

Product labels use the product barcode when present and SKU otherwise. Label
generation is local and printable through a dedicated 50mm x 30mm view.

## Reports hub

The hub combines sales, top items, and low-stock information under one date
and branch filter. Sales, staff performance, and low-stock datasets can be
exported as CSV without loading an entire historical table into the browser.
