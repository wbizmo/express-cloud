# Express Cloud by Zivora

Express Cloud is a standalone enterprise sales, invoicing, purchasing,
supplier, customer, and physical-inventory platform.

The application was built through 19 implementation sprints and is now entering a phased production-hardening programme.

## Locked technical foundation

- Laravel 13
- PHP 8.4 target; PHP 8.3 minimum
- Blade, Livewire 4, Alpine, and Tailwind CSS
- MySQL-compatible production schema
- NGN as the only v1 transaction currency
- integer-kobo monetary calculations
- selective field encryption
- keyed blind indexes for exact sensitive-value lookup
- no demonstration business data

## Development workflow decision

Development builds the project files only.

The sprint workflow does not install or run:

- Docker;
- MySQL;
- SQLite;
- Redis;
- Codespaces containers;
- database-dependent tests.

Database migrations will be written alongside the relevant features. The
final shared-hosting release will include a generated MySQL installation SQL
file matching those migrations and required system records.

Live deployment is the first full database integration environment under the
user's chosen workflow.

## Locked authentication model

The root route `/` becomes the shared administrator and staff login screen in
Sprint 3.

The login form uses:

- a searchable staff-name combobox;
- no staff email exposure;
- a cryptographically generated access key;
- the format `K7M4-P9XR`;
- no IP banning;
- no permanent account lockout;
- only short-lived endpoint throttling;
- generic invalid-credential responses.

The staff selector requires typed search, returns a fixed-height scrollable
result panel, supports keyboard navigation, and shows branch or department
only when needed to distinguish duplicate names.

Users may view their own assigned access key from their read-only profile.
Authorised administrators may reveal staff keys. Users cannot change names,
roles, login keys, emails, or branch assignments. They may change only their
profile picture.

Access keys are encrypted for authorised display and separately blind-indexed
for exact login lookup.

## Security-event scale

The administration security area must remain usable with anything from zero
records to millions of historical events.

It will use:

- indexed server-side queries;
- bounded date filters;
- cursor pagination;
- server-side sorting;
- searchable event history;
- event detail drawers;
- streamed exports;
- retention and archival rules.

Sensitive plaintext credentials never enter logs, exports, analytics, email,
or Lisa AI context.

## Final customer deliverable

The customer receives:

- the production application ZIP;
- production-built assets;
- Composer dependencies for no-shell deployment;
- one importable MySQL installation SQL file;
- `.env.example`;
- installation documentation;
- administrator, manager, and staff documentation;
- backup, restore, security, and operations documentation;
- checksums and release metadata.

The final ZIP excludes development-only files such as sprint scripts, local
logs, test fixtures, development `.env`, and editor state.

## Quality commands that do not require a database

    composer validate --strict
    composer audit
    vendor/bin/pint --test
    vendor/bin/phpstan analyse
    php artisan test --testsuite=Unit
    npm audit --audit-level=high
    npm run build

## Architecture philosophy

Express Cloud uses a modular Laravel monolith. The architecture prioritizes
correctness, shared-hosting deployment, low operational overhead, strong
authorization boundaries, and long-term maintainability.

Business logic belongs in actions, queries, services, policies, and value
objects rather than controllers or Blade templates.

## Module map

See `docs/architecture/module-map.md`.

## Architecture decisions

Permanent decisions are stored under:

    docs/architecture/decisions/

These records explain the selected approach, alternatives, and consequences.

## Coding standards

See:

    docs/architecture/coding-standards.md

## Git and release workflow

See:

    docs/architecture/git-and-release-workflow.md

## What not to do

- Do not add a second frontend application.
- Do not introduce microservices without a measured operational need.
- Do not store money as floating-point values.
- Do not expose unrestricted business data to Lisa AI.
- Do not place plaintext credentials in logs or exports.
- Do not load entire historical tables into browser state.
- Do not introduce infrastructure dependencies that break shared hosting.
- Do not ship development scripts or environment files in the customer ZIP.

## Interface foundation

Sprint 2 introduces the reusable Express Cloud application shell and design
system.

The desktop sidebar expands to 280px and collapses to 72px. Mobile uses a
dedicated navigation drawer. The shell includes global search placement,
notifications, profile access, a visible logout action, and Lisa AI as a
first-class menu item.

The design system documentation is available at:

    docs/features/design-system.md

A local-only preview is available at:

    /ui-preview

## Authentication implementation

Sprint 3 implements the shared administrator and staff sign-in flow.

Guests use `/`, search for their first or last name, select the correct
account, and enter an eight-character access key.

There is no login email field, password field, IP ban, permanent account
lockout, or role selector.

Authenticated users may review their account information and assigned access
key. They may change only their profile picture.

See:

    docs/features/authentication-and-profiles.md

## Organisation and authorization

Sprint 4 adds company, branch, staff, role, permission, session-control, and
audit-log foundations.

Accounts may be granted all-branch access or explicitly assigned branches.
Custom roles use stable permission slugs, and every sensitive administrative
change is designed to produce a safe audit record.

See:

    docs/features/organisation-roles-and-branches.md

## Organisation and authorization

Sprint 4 adds company, branch, staff, role, permission, session-control, and
audit-log foundations.

Accounts may be granted all-branch access or explicitly assigned branches.
Custom roles use stable permission slugs, and every sensitive administrative
change is designed to produce a safe audit record.

See:

    docs/features/organisation-roles-and-branches.md

## Organisation and authorization

Sprint 4 adds company, branch, staff, role, permission, session-control, and
audit-log foundations.

Accounts may be granted all-branch access or explicitly assigned branches.
Custom roles use stable permission slugs, and every sensitive administrative
change is designed to produce a safe audit record.

See:

    docs/features/organisation-roles-and-branches.md

## Catalogue and suppliers

Sprint 5 adds the product catalogue, categories, brands, tax rates,
suppliers, tracked/untracked item behaviour, default pricing, and optional
branch price overrides.

Stock changes remain deliberately separate from product creation and editing.

See:

    docs/features/catalog-suppliers-and-pricing.md

## Spreadsheet runtime requirements

Product Excel import and export uses the maintained
`phpoffice/phpspreadsheet` package.

Production PHP must enable:

- `ext-zip`
- `ext-gd`
- `ext-dom`
- `ext-fileinfo`
- `ext-iconv`
- `ext-libxml`
- `ext-mbstring`
- `ext-simplexml`
- `ext-xml`
- `ext-xmlreader`
- `ext-xmlwriter`
- `ext-zlib`

Codespaces may install the Composer package while ignoring only `ext-zip`
and `ext-gd` because this build environment is used for code generation and
static verification. The final shared-hosting environment must enable both
extensions before deployment.

## Product Excel imports

Sprint 6 adds a validate-first Excel product-import workflow.

The Product Imports page includes a working **Download Sample Excel File**
button. Uploaded `.xlsx` files are normalized, validated, previewed, and only
processed after explicit confirmation.

See:

    docs/features/product-excel-imports.md

## Inventory ledger

Sprint 7 adds per-branch balances and an immutable stock movement ledger.
Stock intake, transfer, and adjustment are separate from product creation.

See:

    docs/features/inventory-ledger.md

## Procurement and low stock

Sprint 8 adds purchase orders, approved goods receipt, supplier-linked stock
intake, and automatically maintained low-stock alerts.

See:

    docs/features/procurement-and-low-stock.md

## Customers and payment methods

Sprint 9 adds customer records and managed payment methods for the unified
sales engine.

See:

    docs/features/customers-and-payment-methods.md

## Unified sales engine

Sprint 10 adds invoices, quotes, and POS transactions through one idempotent,
transaction-safe engine.

See:

    docs/features/unified-sales-engine.md

## Supplier finance

Sprint 11 adds supplier bills, bill payments, supporting documents, supplier
returns, and outstanding supplier balances.

See:

    docs/features/supplier-finance.md

## Alerts and operations dashboard

Sprint 12 implements Phase 4 of the standalone master specification: admin
dashboard, low-stock notifications, alert recipients, end-of-day digest,
business settings, active sessions, and staff-performance reporting.

Express Cloud is not an accounting system.

See:

    docs/features/alerts-dashboard-and-digests.md

## Documents and reporting exports

Sprint 13 adds thermal receipts, A4 invoices and quotes, PDF downloads, local
verification QR codes, product labels, a unified reports hub, and CSV exports.

See:

    docs/features/documents-and-reporting-exports.md

## Activity and production hardening

Sprint 14 adds product history, the system activity log, live session
management, structured production errors, and targeted regression protection.

See:

    docs/features/activity-sessions-and-error-hardening.md

## Commercial controls

Sprint 15 adds customer receivables, credit settlement, sale returns,
discount vouchers, direct purchase recording, creator-scoped sales access,
and system-wide enterprise form controls.

See:

    docs/features/commercial-controls.md

## API and quote conversion

Sprint 16 adds quote conversion, hashed bearer tokens, the versioned API, and
OpenAPI documentation.

See:

    docs/api/openapi.md
    docs/features/api-and-quote-conversion.md

## Backups and recovery

Sprint 17 adds checksummed database backups, configurable off-server storage,
verification tooling, retention pruning, health checks, and recovery
documentation.

See:

    docs/features/backups-and-recovery.md
    docs/deployment/production-readiness.md
    docs/deployment/recovery-runbook.md

## Operational accounting and documents

Sprint 18 adds standalone receipts, purchase returns, the fixed-asset
register, optional company-logo branding, and downloadable operational PDF
and spreadsheet reports.

See:

    docs/features/operational-accounting-and-documents.md

## Sprint 19: accounting and final release

The final sprint adds the chart of accounts, periods, journals, operational
posting, trial balance, general ledger, historical COGS, depreciation,
production bootstrap seeding, SQL generation, and the no-shell release package.

Release build:

    bash release/build-release.sh

Generated files:

    release/express-cloud-install.sql
    release/FIRST_LOGIN.txt
    release/express-cloud-release.zip
