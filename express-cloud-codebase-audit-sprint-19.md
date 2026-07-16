# Express Cloud Codebase Audit and Sprint 19 Scope

## Audit basis

This review covers the uploaded `express-cloud-main.zip`, the Express Cloud
master specification, the SalesPlus product and engineering master sheets,
the committed Sprint 15–18 reports, migrations, routes, models, actions,
tests, deployment documents, and the new scope decisions made during the
build.

The repository contains Sprint reports through Sprint 18. Sprint 18 explicitly
states that full double-entry accounting is deferred to Sprint 19. That is an
intentional scope override of the original Express Cloud master specification,
which originally excluded accounting.

## Current implementation status

### Stable foundations already present

- key-based staff authentication with encrypted access keys and blind indexes;
- companies, branches, staff, roles, permissions, session controls, and audit logs;
- product categories, brands, tax rates, suppliers, products, branch pricing,
  Excel product imports, sample templates, and product activity;
- append-only inventory movements, stock intake, stock transfers, stock
  adjustment, zero-stock and low-stock reporting;
- purchase orders, goods receipt, suppliers, supplier bills, supplier payments,
  supplier returns, and supplier balance reporting;
- customers, payment methods, POS/invoice/quote sales, quote conversion,
  partial payments, credit balances, customer receivables, reusable vouchers,
  sale returns, direct purchase recording, and creator-scoped staff sale access;
- PDFs, thermal documents, A4 documents, product barcode labels, document hashes,
  report exports, activity logs, live sessions, operations dashboard, alerts,
  end-of-day digest, API tokens, versioned API, backup operations, and health checks;
- Sprint 18 operational finance records: standalone receipts, purchase returns,
  fixed assets, branding, operational PDFs, and spreadsheets.

### Code quality observations

All PHP files pass `php -l` syntax validation. The repository was uploaded
without `vendor/`, `node_modules/`, or `.git`, so the full PHPUnit, Larastan,
Composer audit, and production build could not be rerun in this isolated
environment.

## Release blockers found

### Critical

1. **The production seeder is broken.**
   `database/seeders/DatabaseSeeder.php` still imports the deleted
   `App\Models\User` class and calls the removed default user factory.
   A fresh installation cannot seed successfully.

2. **There is no production bootstrap dataset.**
   The repository does not create the initial company, head-office branch,
   administrator, owner role, permissions, payment methods, business settings,
   document branding, accounting periods, or chart of accounts.

3. **There is no installable SQL artifact.**
   The release policy promises an importable MySQL SQL file, but none is
   present and there is no reproducible release command that creates one.

4. **There is no complete no-shell release builder.**
   The requested customer package must contain built assets, `vendor/`,
   application files, SQL, installation documentation, and first-login
   credentials. Current repository tooling does not assemble that package.

5. **Sprint 18 is not a full accounting system.**
   It creates operational finance records but has no ledger accounts,
   journal entries, journal lines, accounting periods, posting rules,
   trial balance, general ledger, profit and loss, balance sheet, journal
   reversals, or period locking.

### High priority

6. **Historical cost of goods sold cannot be calculated reliably.**
   `sale_items` stores price and tax snapshots but not a unit-cost snapshot.
   Using the current product cost later would rewrite historical margin.
   Sprint 19 must add `unit_cost_kobo_snapshot`.

7. **Operational finance records are not projected into a ledger.**
   Sales, payments, purchases, supplier bills, supplier payments, returns,
   standalone receipts, fixed assets, and depreciation currently have no
   idempotent accounting projection.

8. **API documentation and implementation are slightly out of sync.**
   The API documentation still describes quote conversion while the current
   API route file exposes products, customers, sales, reports, health, and
   OpenAPI but no quote-conversion endpoint.

9. **Some permissions overlap.**
   The catalogue group contains `sales.view`, while the Commercial group also
   contains `sales.view.own` and `sales.view.all`. Sprint 19 should retain
   compatibility but document and normalize intended usage.

10. **Backup configuration advertises upload inclusion but current backup
    creation only archives the database dump and manifest.**
    This should be documented accurately or completed before release.

### Medium priority

11. `PurchaseReceipt` list rendering shows raw supplier identifiers instead of
    supplier names because the model lacks supplier and branch relations in the
    current implementation.

12. The global JavaScript control enhancer sorts native select options but does
    not provide a true searchable combobox for every long list. Large product,
    customer, staff, and supplier datasets should use server-backed search
    rather than loading every option.

13. The health endpoint performs a write/delete probe on backup storage for
    every request. That is acceptable for an administrative readiness probe but
    should not be polled at high frequency.

14. The codebase contains both supplier returns and purchase returns. They serve
    related but different workflows and must remain clearly named in the UI and
    documentation to avoid duplicate operational entry.

## Sprint 19 final scope

### A. Full double-entry accounting

Sprint 19 must add:

- chart of accounts and account types;
- accounting periods with open, closed, and locked states;
- journal entries and journal lines;
- debit/credit balancing enforced before posting;
- idempotent source references;
- manual journals with mandatory memo;
- journal reversals instead of destructive edits;
- general ledger;
- trial balance;
- profit and loss;
- balance sheet;
- accounts receivable and accounts payable control accounts;
- cash/bank/payment-method clearing accounts;
- sales revenue, sales returns, output tax, inventory, COGS, purchase returns,
  fixed assets, accumulated depreciation, depreciation expense, customer
  deposits, liabilities, and equity accounts;
- fixed-asset depreciation posting;
- locked-period enforcement;
- an accounting synchronization command for existing operational records;
- immediate projections for newly created operational records;
- accounting permissions, routes, interfaces, tests, and documentation.

### B. Historical cost integrity

- add `unit_cost_kobo_snapshot` to `sale_items`;
- populate it when a sale or quote is created;
- preserve it during quote conversion;
- backfill existing rows from the product cost only as a migration fallback;
- use the snapshot for COGS postings.

### C. Production bootstrap seeding

Replace the stale default seeder with a production installation seeder that:

- requires explicit installation environment variables;
- creates the company and head-office branch;
- creates every permission in `PermissionCatalog`;
- creates the system owner role and attaches all permissions;
- generates or accepts an eight-character access key;
- encrypts the access key and stores its blind index;
- creates the initial administrator;
- creates default Cash, Bank Transfer, Card/POS, and Customer Credit payment methods;
- creates document branding, business settings, the first open accounting period,
  and the default chart of accounts;
- never stores the plaintext access key in the database.

### D. Raw SQL generation

The release builder must:

1. create a temporary MySQL database;
2. run `migrate:fresh --seed` against it;
3. run accounting synchronization;
4. use `mysqldump` to create `express-cloud-install.sql`;
5. include schema, seeded configuration, chart of accounts, administrator
   account, and migration history;
6. store the one-time plaintext access key only in `FIRST_LOGIN.txt`;
7. drop the temporary database after a successful dump.

### E. Final no-shell package

Create a ZIP containing:

- application source;
- `vendor/`;
- built `public/build/` assets;
- `express-cloud-install.sql`;
- `.env.example`;
- `FIRST_LOGIN.txt`;
- installation, upgrade, backup, recovery, and operations documentation;
- license and release manifest.

Exclude:

- `.env`;
- `.git`;
- sprint scripts;
- test artifacts;
- `node_modules`;
- temporary logs;
- local backups;
- `public/hot`;
- development caches.

## SQL generation limitation in this review environment

A trustworthy final SQL dump cannot be created before Sprint 19 is applied,
because the Sprint 19 accounting migration and production seed data do not yet
exist. This environment also has no Composer installation, no PDO MySQL
driver, no MySQL server, and no network access to install them.

The Sprint 19 script therefore contains the reproducible SQL and release
generation process. When it runs in the project Codespace or release host with
MySQL, Composer, Node, `mysqldump`, and `zip`, it generates:

- `release/express-cloud-install.sql`
- `release/FIRST_LOGIN.txt`
- `release/express-cloud-release.zip`

The SQL should only be distributed after the script's full test, PHPStan,
Composer audit, npm audit, migration, seeding, accounting synchronization, and
dump verification stages pass.
