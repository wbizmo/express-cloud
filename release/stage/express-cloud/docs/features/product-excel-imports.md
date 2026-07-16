# Product Excel Imports

## User flow

1. Open Product Imports.
2. Click **Download Sample Excel File**.
3. Complete the `Products` worksheet.
4. Upload an `.xlsx` workbook.
5. Review validation totals and up to fifty preview rows.
6. Download the error workbook when validation fails.
7. Confirm a fully valid import.
8. Review the completed import history.

No product is created or updated during preview.

## Workbook

The sample workbook contains:

- `Products`;
- `Instructions`;
- `Reference`.

The product columns are:

- sku;
- name;
- barcode;
- category;
- brand;
- supplier_code;
- supplier_name;
- tax_rate_percent;
- default_price;
- default_cost_price;
- track_inventory;
- description;
- status.

## Validation

The validator checks:

- required columns;
- required product fields;
- allowed values;
- money and tax ranges;
- duplicate SKU within the workbook;
- duplicate barcode within the workbook;
- maximum lengths;
- valid Excel file type.

Rows are stored with their normalized payload and row-specific errors.

## Import behavior

- Existing SKU updates the product.
- New SKU creates the product.
- Missing categories and brands are created.
- Supplier code is authoritative when supplied.
- Missing suppliers may be created from workbook data.
- Matching tax percentages reuse a tax rate.
- Product quantities are never imported here.
- Product images are never fetched from workbook URLs.
- The entire confirmed import runs inside one database transaction.
- A processing failure rolls back product changes.

## Scale and resilience

Row metadata is inserted in bounded batches.

Confirmed rows are streamed with a database cursor rather than loading the
entire import into memory.

The architecture can later move processing into a queue without changing the
controller or import data contract. Redis is not required for correctness.

## Error workbook

Invalid imports expose an Excel error report containing the original row data
and a final `errors` column.

The report is permission protected and stored outside the public web root.
