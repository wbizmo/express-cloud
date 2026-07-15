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
