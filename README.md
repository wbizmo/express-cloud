# Express Cloud by Zivora

Express Cloud is a standalone enterprise sales, invoicing, purchasing,
supplier, customer, and physical-inventory platform.

The application is being built through 17 implementation sprints.

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
