#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SPRINT="1A"
SCRIPT_PATH="$(readlink -f "$0")"
SCRIPT_NAME="$(basename "$0")"
LOG_DIR=".sprint-logs"
LOG_FILE="${LOG_DIR}/sprint-1a-$(date -u +%Y%m%dT%H%M%SZ).log"
SKIP_PUSH="${SKIP_PUSH:-0}"

mkdir -p "${LOG_DIR}"
exec > >(tee -a "${LOG_FILE}") 2>&1

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "SPRINT ${SPRINT} FAILED"
    echo "Line: ${line_no}"
    echo "Exit code: ${exit_code}"
    echo "Log retained at: ${LOG_FILE}"
    echo "============================================================"
    exit "${exit_code}"
}

trap 'fail "$LINENO"' ERR

section() {
    echo
    echo "============================================================"
    echo "$1"
    echo "============================================================"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "Required command not found: $1"
        exit 1
    }
}

git_commit_if_needed() {
    local message="$1"

    git add -A

    if git diff --cached --quiet; then
        echo "No changes to commit for: ${message}"
        return 0
    fi

    git commit -m "${message}"
}

push_current_branch() {
    if [[ "${SKIP_PUSH}" == "1" ]]; then
        echo "SKIP_PUSH=1; push skipped."
        return 0
    fi

    if ! git remote get-url origin >/dev/null 2>&1; then
        echo "No origin remote exists."
        echo "Configure origin or rerun with SKIP_PUSH=1."
        exit 1
    fi

    git push -u origin "$(git branch --show-current)"
}

section "Sprint ${SPRINT}: preflight"

require_command git
require_command php
require_command composer
require_command npm
require_command node
require_command python3

if [[ ! -d .git ]]; then
    echo "Run this script from the Express Cloud repository root."
    exit 1
fi

if [[ ! -f artisan || ! -f composer.json || ! -f package.json ]]; then
    echo "Laravel project files were not found."
    exit 1
fi

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Tracked files contain uncommitted changes."
    echo "Commit or revert them before Sprint 1A."
    git status --short
    exit 1
fi

if ! git log --oneline --all --grep='feat(sprint-01): establish database-independent Laravel foundation' | grep -q .; then
    echo "Sprint 1 implementation commit was not found."
    exit 1
fi

if ! git log --oneline --all --grep='test(docs): complete sprint-01 verification and documentation' | grep -q .; then
    echo "Sprint 1 documentation commit was not found."
    exit 1
fi

section "Cleaning audit and abandoned Sprint 1 files"

rm -f \
    engineering-audit-report.txt \
    express-cloud-sprint-01-files-only.sh \
    express-cloud-sprint-01-repair.sh \
    express-cloud-sprint-01-v2.sh \
    express-cloud-sprint-01-v3.sh \
    express-cloud-sprint-01-v4.sh \
    express-cloud-sprint-01-v5.sh \
    cleanup-sprint-01.sh

rm -rf /tmp/express-cloud-bootstrap

section "Removing Laravel starter artifacts"

rm -f tests/Feature/ExampleTest.php
rm -f resources/views/welcome.blade.php

section "Creating permanent route structure"

cat > routes/auth.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Sprint 3 will implement the shared administrator and staff login flow.
|
*/

Route::view('/', 'auth.login-placeholder')->name('login');
PHP

cat > routes/admin.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administrator Routes
|--------------------------------------------------------------------------
|
| Protected administrator routes will be registered here from Sprint 3.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        //
    });
PHP

cat > routes/staff.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| Protected staff routes will be registered here from Sprint 3.
|
*/

Route::prefix('staff')
    ->name('staff.')
    ->group(function (): void {
        //
    });
PHP

cat > routes/api.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes are intentionally empty until an approved feature requires them.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        //
    });
PHP

cat > routes/web.php <<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
PHP

python3 - <<'PY'
from pathlib import Path

path = Path("bootstrap/app.php")
text = path.read_text()

old = """    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )"""

new = """    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )"""

if old not in text:
    raise SystemExit("Expected bootstrap routing block was not found.")

path.write_text(text.replace(old, new, 1))
PY

section "Creating login placeholder"

mkdir -p resources/views/auth

cat > resources/views/auth/login-placeholder.blade.php <<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Express Cloud by Zivora">
    <title>Sign in | Express Cloud by Zivora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <main class="grid min-h-screen place-items-center p-6">
        <section class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                by Zivora
            </p>

            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Express Cloud
            </h1>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                The shared administrator and staff login interface will be
                implemented in Sprint 3.
            </p>
        </section>
    </main>
</body>
</html>
BLADE

section "Creating application architecture directories"

for directory in \
    app/Actions \
    app/DTOs \
    app/Enums \
    app/Events \
    app/Exceptions \
    app/Jobs \
    app/Policies \
    app/Queries \
    app/Repositories \
    app/Rules \
    app/Services \
    app/Traits \
    app/ValueObjects \
    app/ViewModels
do
    mkdir -p "${directory}"
    cat > "${directory}/.gitkeep" <<'EOF'
EOF
done

section "Creating architecture decision records"

mkdir -p docs/architecture/decisions

cat > docs/architecture/decisions/ADR-001-framework.md <<'MD'
# ADR-001: Laravel Framework

## Status

Accepted.

## Decision

Use Laravel 13 with PHP 8.4 as the target runtime.

## Context

The product must support shared-hosting deployment, conventional PHP
maintenance, and a single application deployment unit.

## Alternatives

- React frontend plus API backend
- Node.js full-stack application
- Laravel modular monolith

## Consequences

Laravel provides routing, validation, authorization, mail, queues, exports,
and deployment portability inside one codebase.
MD

cat > docs/architecture/decisions/ADR-002-money.md <<'MD'
# ADR-002: NGN Integer Money Model

## Status

Accepted.

## Decision

Express Cloud v1 supports NGN only. Monetary values are represented in
integer kobo.

## Consequences

The application avoids floating-point arithmetic and does not expose a
currency selector in v1.
MD

cat > docs/architecture/decisions/ADR-003-authentication.md <<'MD'
# ADR-003: Shared Staff-Selector Authentication

## Status

Accepted.

## Decision

Administrators and staff use one login screen at `/`.

The form uses a searchable staff-name combobox and an eight-character access
key formatted like `K7M4-P9XR`.

## Security constraints

- No email addresses appear in the selector.
- No automatic IP banning.
- No permanent account lockout.
- Short-lived endpoint throttling is permitted.
- Invalid authentication responses remain generic.
MD

cat > docs/architecture/decisions/ADR-004-encryption.md <<'MD'
# ADR-004: Selective Field Encryption

## Status

Accepted.

## Decision

Passwords use one-way hashing.

Viewable login keys and selected sensitive employee fields use versioned
encryption. Exact login-key lookup uses a separate HMAC blind index.

## Consequences

Encryption keys remain outside the database and are separated by purpose.
Sensitive plaintext never enters logs, exports, analytics, email, or Lisa AI
context.
MD

cat > docs/architecture/decisions/ADR-005-modular-monolith.md <<'MD'
# ADR-005: Modular Monolith

## Status

Accepted.

## Decision

Build Express Cloud as a modular Laravel monolith.

## Rationale

A modular monolith avoids network latency, distributed transactions,
duplicated authorization logic, and unnecessary deployment complexity.

## Boundaries

Controllers coordinate requests. Actions implement use cases. Queries produce
read models. Policies authorize access. Services enforce reusable domain
rules. Views present already-authorized data.
MD

cat > docs/architecture/decisions/ADR-006-mysql.md <<'MD'
# ADR-006: MySQL Production Target

## Status

Accepted.

## Decision

The production and release database target is MySQL.

Development sprints write migrations but do not run database services under
the locked project-files-only workflow.

## Release consequence

The final package includes one MySQL installation SQL file matching the
application schema and required system records.
MD

cat > docs/architecture/decisions/ADR-007-livewire.md <<'MD'
# ADR-007: Blade and Livewire

## Status

Accepted.

## Decision

Use Blade and Livewire for reactive application screens, with small isolated
browser-side JavaScript only where the browser must perform the work.

## Consequences

The application retains one authorization and deployment boundary without
requiring a separately deployed React frontend.
MD

cat > docs/architecture/decisions/ADR-008-lisa-ai.md <<'MD'
# ADR-008: Lisa AI Architecture

## Status

Accepted.

## Decision

Lisa AI by Zivora is a first-class navigation module.

Its core intelligence uses deterministic, permission-aware query handlers and
a versioned product knowledge base. An optional language-model provider may
improve wording and intent classification later.

## Security constraints

- Lisa receives only already-authorized data.
- Lisa never generates arbitrary production SQL.
- Lisa respects company, branch, role, and user scope.
- Login keys and sensitive credentials never enter Lisa context.
MD

section "Adding engineering standards"

cat > docs/architecture/coding-standards.md <<'MD'
# Coding Standards

## PHP

- Use strict types.
- Follow Laravel Pint formatting.
- Pass Larastan at the configured level.
- Keep controllers thin.
- Use Form Requests for external input validation.
- Use policies and middleware for authorization.
- Use actions for state-changing use cases.
- Use query objects for analytics and read models.
- Do not place business logic inside Blade templates.

## Database design

- Use explicit indexes for common filters and ordering.
- Use integer kobo for money.
- Use append-only ledgers for inventory and sensitive histories.
- Avoid unbounded list queries.
- Use transactions for financial and inventory writes.
- Document migration intent with each feature.

## Frontend

- Build reusable Blade and Livewire components.
- Use consistent loading, success, error, and disabled states.
- Preserve responsive desktop and mobile behaviour.
- Avoid visual clutter and unnecessary dashboard decoration.
MD

cat > docs/architecture/git-and-release-workflow.md <<'MD'
# Git and Release Workflow

## Sprint commits

Each sprint normally produces:

1. implementation commit;
2. tests and documentation commit.

## Commit prefixes

- `feat`
- `fix`
- `refactor`
- `test`
- `docs`
- `chore`
- `security`
- `perf`

## Release

The final v1.0.0 package includes the application, built assets, Composer
dependencies for no-shell deployment, one MySQL installation SQL file,
documentation, checksums, and release metadata.

Development scripts and local logs are excluded.
MD

cat > docs/architecture/module-map.md <<'MD'
# Module Map

Planned product modules:

- Authentication and profiles
- Company and branches
- Staff, roles, and permissions
- Suppliers and classifications
- Products
- Imports
- Inventory
- Purchasing
- Sales
- Invoices and quotations
- Payments and returns
- Transfers and stock counts
- Dashboards and reporting
- Lisa AI
- Security events and audit logs
- Cron, backups, and operational resilience
MD

section "Expanding README"

cat >> README.md <<'MD'

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
MD

section "Disabling Xdebug noise for this sprint process"

export XDEBUG_MODE=off

section "Sprint 1A implementation verification"

vendor/bin/pint
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
php artisan test --testsuite=Unit
composer validate --strict
composer audit
npm audit --audit-level=high
npm run build
php artisan route:list

if [[ -e database/database.sqlite ]]; then
    echo "SQLite artifact detected."
    exit 1
fi

if [[ -f public/hot ]]; then
    echo "public/hot exists."
    exit 1
fi

if [[ -f tests/Feature/ExampleTest.php ]]; then
    echo "Laravel starter feature test still exists."
    exit 1
fi

if [[ -f resources/views/welcome.blade.php ]]; then
    echo "Laravel welcome view still exists."
    exit 1
fi

if [[ -e engineering-audit-report.txt ]]; then
    echo "Engineering audit report was not cleaned."
    exit 1
fi

git diff --check

git_commit_if_needed \
    "refactor(sprint-1a): strengthen project foundation"

push_current_branch

section "Sprint 1A documentation verification"

required_docs=(
    docs/architecture/decisions/ADR-001-framework.md
    docs/architecture/decisions/ADR-002-money.md
    docs/architecture/decisions/ADR-003-authentication.md
    docs/architecture/decisions/ADR-004-encryption.md
    docs/architecture/decisions/ADR-005-modular-monolith.md
    docs/architecture/decisions/ADR-006-mysql.md
    docs/architecture/decisions/ADR-007-livewire.md
    docs/architecture/decisions/ADR-008-lisa-ai.md
    docs/architecture/coding-standards.md
    docs/architecture/git-and-release-workflow.md
    docs/architecture/module-map.md
)

for document in "${required_docs[@]}"; do
    if [[ ! -s "${document}" ]]; then
        echo "Required documentation is missing or empty: ${document}"
        exit 1
    fi
done

vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
php artisan test --testsuite=Unit
npm run build

git_commit_if_needed \
    "docs: add architecture decisions and engineering standards"

push_current_branch

section "Sprint 1A cleanup"

rm -f \
    engineering-audit-report.txt \
    express-cloud-sprint-1a.sh

rm -rf "${LOG_DIR}"

echo
echo "Final repository status:"
git status --short

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Repository is not clean after Sprint 1A."
    exit 1
fi

rm -f -- "${SCRIPT_PATH}"

echo
echo "============================================================"
echo "SPRINT ${SPRINT} COMPLETED"
echo "============================================================"
echo
echo "Expected commits:"
echo "  refactor(sprint-1a): strengthen project foundation"
echo "  docs: add architecture decisions and engineering standards"
