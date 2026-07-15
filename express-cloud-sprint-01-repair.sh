#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SPRINT="01"
SKIP_PUSH="${SKIP_PUSH:-0}"
SCRIPT_PATH="$(readlink -f "$0")"

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "SPRINT ${SPRINT} REPAIR FAILED"
    echo "Line: ${line_no}"
    echo "Exit code: ${exit_code}"
    echo "The repair script was retained for inspection."
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

section "Repairing Sprint 1 static-analysis failures"

if [[ ! -d .git ]]; then
    echo "This repair must run from the Express Cloud Git repository root."
    exit 1
fi

if [[ ! -f app/Support/Money/Naira.php ]]; then
    echo "Sprint 1 generated files were not found."
    echo "Do not use this repair script on a clean repository."
    exit 1
fi

python3 - <<'PY'
from pathlib import Path

naira = Path("app/Support/Money/Naira.php")
text = naira.read_text()

old = """    public function jsonSerialize(): array
    {
        return [
"""

new = """    /**
     * @return array{currency: string, amount_minor: int, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
"""

if old not in text:
    raise SystemExit("Expected Naira::jsonSerialize block was not found.")

naira.write_text(text.replace(old, new, 1))

config = Path("config/app.php")
config_text = config.read_text()

old_timezone = "'timezone' => 'UTC',"
new_timezone = "'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),"

if old_timezone in config_text:
    config_text = config_text.replace(old_timezone, new_timezone, 1)
elif new_timezone not in config_text:
    raise SystemExit("Expected timezone configuration was not found.")

config.write_text(config_text)
PY

rm -f tests/Unit/ExampleTest.php

vendor/bin/pint
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
composer validate --strict
composer audit
php artisan about
php artisan route:list
npm audit --audit-level=high
npm run build

if php artisan about | grep -E 'Timezone[[:space:]]+UTC' >/dev/null; then
    echo "Application timezone is still UTC."
    exit 1
fi

if [[ -e database/database.sqlite ]]; then
    echo "SQLite artifact detected."
    exit 1
fi

git_commit_if_needed \
    "feat(sprint-01): establish database-independent Laravel foundation"

push_current_branch

section "Adding unit tests and documentation"

cat > tests/Unit/NairaTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money\Naira;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NairaTest extends TestCase
{
    public function test_values_are_stored_as_integer_kobo(): void
    {
        self::assertSame(1_250_075, Naira::fromNaira('12500.75')->kobo);
    }

    public function test_values_format_without_floating_point_arithmetic(): void
    {
        self::assertSame('₦12,500.75', Naira::fromKobo(1_250_075)->format());
        self::assertSame('₦12,500', Naira::fromKobo(1_250_000)->format());
    }

    public function test_arithmetic_is_immutable(): void
    {
        $base = Naira::fromNaira('1000');
        $total = $base->multiply(3)->add(Naira::fromNaira('250'));

        self::assertSame(100_000, $base->kobo);
        self::assertSame(325_000, $total->kobo);
    }

    public function test_invalid_precision_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Naira::fromNaira('10.999');
    }
}
PHP

cat > tests/Unit/EncryptedValueTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\EncryptedValue;
use PHPUnit\Framework\TestCase;

final class EncryptedValueTest extends TestCase
{
    public function test_sensitive_value_round_trips_without_plaintext_storage(): void
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $service = new EncryptedValue($key, 3);

        $payload = $service->encrypt('EMP-LOGIN-KEY-001');

        self::assertStringNotContainsString('EMP-LOGIN-KEY-001', $payload);
        self::assertSame('EMP-LOGIN-KEY-001', $service->decrypt($payload));
        self::assertStringContainsString('"v":3', $payload);
    }
}
PHP

cat > tests/Unit/BlindIndexTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\BlindIndex;
use PHPUnit\Framework\TestCase;

final class BlindIndexTest extends TestCase
{
    public function test_lookup_index_is_deterministic_and_normalized(): void
    {
        $service = new BlindIndex('test-blind-index-key');

        self::assertSame(
            $service->make(' Staff-Key-001 '),
            $service->make('staff-key-001'),
        );
    }

    public function test_different_values_have_different_indexes(): void
    {
        $service = new BlindIndex('test-blind-index-key');

        self::assertNotSame(
            $service->make('staff-key-001'),
            $service->make('staff-key-002'),
        );
    }
}
PHP

cat > tests/Unit/LoginKeyGeneratorTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\LoginKeyGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LoginKeyGeneratorTest extends TestCase
{
    public function test_generated_keys_are_grouped_for_readability(): void
    {
        $key = (new LoginKeyGenerator())->generate();

        self::assertMatchesRegularExpression(
            '/^[A-HJ-KM-NP-Z2-9]{4}-[A-HJ-KM-NP-Z2-9]{4}$/',
            $key,
        );
    }

    public function test_keys_use_no_ambiguous_characters(): void
    {
        $generator = new LoginKeyGenerator();

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $key = $generator->generate();

            self::assertStringNotContainsString('0', $key);
            self::assertStringNotContainsString('O', $key);
            self::assertStringNotContainsString('1', $key);
            self::assertStringNotContainsString('I', $key);
            self::assertStringNotContainsString('L', $key);
        }
    }

    public function test_normalization_accepts_grouped_keys(): void
    {
        self::assertSame(
            'K7M4P9XR',
            LoginKeyGenerator::normalize('k7m4-p9xr'),
        );
    }

    public function test_invalid_keys_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LoginKeyGenerator::normalize('12345678');
    }
}
PHP

cat > tests/Unit/SensitiveDataTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\SensitiveData;
use PHPUnit\Framework\TestCase;

final class SensitiveDataTest extends TestCase
{
    public function test_sensitive_fields_are_registered_for_redaction(): void
    {
        self::assertContains('password', SensitiveData::forbiddenLogFields());
        self::assertContains('login_key', SensitiveData::forbiddenLogFields());
        self::assertContains(
            'data_encryption_key',
            SensitiveData::forbiddenLogFields(),
        );
    }
}
PHP

cat > README.md <<'MD'
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
MD

cat > docs/DOCUMENTATION.txt <<'TXT'
EXPRESS CLOUD BY ZIVORA
ENGINEERING DOCUMENTATION INDEX
VERSION: 1.0.0-dev

Express Cloud is developed under a 17-sprint plan.

LOCKED DEVELOPMENT WORKFLOW

- Build project files only during the implementation sprints.
- Do not run Docker, MySQL, SQLite, Redis, or container services.
- Do not perform database-dependent testing in Codespaces.
- Write production-targeted MySQL migrations with each feature.
- Generate and package one MySQL installation SQL file at release.
- Perform the first full database integration test after live deployment.

LOCKED PRODUCT RULES

- MySQL is the production target.
- NGN is the only v1 transaction currency.
- Money is represented in integer kobo.
- No demonstration business data ships.
- Sensitive credentials are encrypted selectively.
- Passwords remain one-way hashed.
- Viewable access keys use encrypted ciphertext plus a blind index.
- Business writes must create audit events.
- Security histories must scale through server-side querying and pagination.
TXT

cat > docs/architecture/overview.md <<'MD'
# Architecture Overview

Express Cloud is a modular Laravel monolith.

Controllers coordinate HTTP requests. Form requests validate input. Policies
and middleware authorize access. Application actions coordinate use cases.
Query objects calculate read models and analytics. Domain services enforce
business invariants. Models map persistence. Views and Livewire components
present already-authorized data.

The production database target is MySQL. During implementation, migrations
define the intended schema but no database service is run locally under the
locked workflow.

Scale begins with bounded queries, correct indexes, server-side pagination,
transactional writes, append-only ledgers, and streamed exports. Caching is
introduced only where measured and operationally safe.
MD

cat > docs/architecture/authentication-and-security-events.md <<'MD'
# Authentication and Security Events

## Shared login

Guests visiting `/` see the shared administrator and staff login page.

The form contains:

1. searchable staff-name selector;
2. access key.

The selector does not reveal staff email addresses. It requires typed search,
uses a fixed-height scrollable result list, supports keyboard navigation, and
shows branch or department when duplicate names require disambiguation.

## Access keys

Access keys use eight non-ambiguous uppercase alphanumeric characters,
displayed as:

    K7M4-P9XR

Approved alphabet:

    ABCDEFGHJKMNPQRSTUVWXYZ23456789

The plaintext key is not stored. The application stores encrypted ciphertext
for authorised display and a keyed blind index for exact login lookup.

## No bans or permanent lockouts

Express Cloud does not automatically ban IP addresses and does not
permanently lock accounts after failed login attempts.

The endpoint may apply short-lived throttling that expires automatically.

## Security-event scale

Security histories must support millions of records through:

- indexed filters;
- bounded date ranges;
- server-side sorting;
- cursor pagination;
- immutable identifiers;
- retention and archival;
- streamed exports.

Sensitive plaintext values are never stored inside event payloads.
MD

cat > docs/architecture/encryption-and-key-management.md <<'MD'
# Encryption and Key Management

Passwords are one-way hashed and never reversibly encrypted.

Viewable login keys and selected employee information use versioned encrypted
payloads. Exact login lookup uses a separate HMAC blind index.

Keys are separated by purpose:

    APP_KEY
    DATA_ENCRYPTION_KEY
    BLIND_INDEX_KEY
    BACKUP_ENCRYPTION_KEY
    CRON_PATH_SECRET

Sensitive values must not appear in logs, exports, analytics, exception
messages, email, or Lisa AI context.

Database encryption primarily protects against database-only disclosure. It
does not protect data when an attacker also obtains application keys or a
valid privileged session.
MD

cat > docs/deployment/release-package-policy.md <<'MD'
# Release Package Policy

The final customer deliverable contains the application, built assets,
Composer dependencies for no-shell deployment, an importable MySQL SQL file,
and complete installation and operating documentation.

The release excludes:

- sprint shell scripts;
- temporary sprint logs;
- development `.env`;
- local test artifacts;
- editor state;
- `public/hot`;
- development source maps not required in production.

No production `.env` file is shipped.
MD

cat > docs/testing/strategy.md <<'MD'
# Testing Strategy

Under the locked workflow, Codespaces testing is limited to checks that do
not require a database service:

- unit tests;
- PHP syntax and static analysis;
- formatting;
- Composer validation and advisories;
- frontend build;
- npm advisories.

Database integration, SQL import, foreign keys, migrations, transactions,
authentication persistence, inventory concurrency, and live reports are
validated after deployment with the packaged MySQL SQL file.
MD

cat > docs/sprints/sprint-01-report.md <<'MD'
# Sprint 1 Report

Sprint 1 establishes:

- Laravel 13 foundation;
- Livewire and Tailwind;
- NGN integer-kobo value object;
- encrypted-value service;
- blind-index service;
- readable secure access-key generator;
- sensitive-field redaction registry;
- database-independent quality tooling;
- authentication architecture;
- security-event scale rules;
- final release packaging policy.

No database engine, container service, or database-dependent test is used.
MD

cat > CHANGELOG.md <<'MD'
# Changelog

## [Unreleased]

### Added

- Laravel 13 application foundation
- Livewire 4 and Tailwind CSS
- NGN integer-kobo money model
- versioned sensitive-field encryption
- keyed blind indexing
- readable secure access-key generation
- shared-login architecture
- scalable security-event architecture
- release packaging policy
MD

cat > SECURITY.md <<'MD'
# Security Policy

Never commit production credentials, encryption keys, database dumps,
plaintext access keys, customer data, or production `.env` files.

Passwords must use one-way password hashing.

Viewable access keys use encrypted ciphertext plus a separate blind index.

Sensitive values must not appear in logs, exports, analytics, email, error
responses, or Lisa AI context.
MD

cat > CONTRIBUTING.md <<'MD'
# Contributing

Work through the approved sprint scope.

Create and modify project files through terminal commands.

Keep controllers thin and business logic outside views.

Update tests and documentation in the same sprint.

Use two commits per sprint:

    feat(sprint-NN): implementation summary
    test(docs): complete sprint-NN verification and documentation
MD

section "Sprint 1 final no-database verification"

php artisan test --testsuite=Unit
vendor/bin/pint
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
composer validate --strict
composer audit
npm audit --audit-level=high
npm run build

if [[ -e database/database.sqlite ]]; then
    echo "SQLite artifact detected."
    exit 1
fi

if find . -maxdepth 2 \
    \( -name 'Dockerfile' \
    -o -name 'compose.yaml' \
    -o -name 'docker-compose.yml' \
    -o -name '.devcontainer' \) \
    -print | grep -q .; then
    echo "Container infrastructure was found unexpectedly."
    exit 1
fi

if [[ -f public/hot ]]; then
    echo "public/hot exists and would leak the development asset server."
    exit 1
fi

if grep -RInE \
    --exclude-dir=.git \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    --exclude='*.lock' \
    --exclude="${SCRIPT_NAME}" \
    --exclude='.env.example' \
    '(localhost|127\.0\.0\.1|:8000|:5173)' \
    app config routes resources docs README.md; then
    echo "Development URL leakage detected in production project files."
    exit 1
fi

if git grep -nE \
    '(APP_KEY=base64:|DATA_ENCRYPTION_KEY=[^[:space:]]+|BLIND_INDEX_KEY=[^[:space:]]+|BACKUP_ENCRYPTION_KEY=[^[:space:]]+|CRON_PATH_SECRET=[^[:space:]]+)' \
    -- ':!.env.example' ":!${SCRIPT_NAME}"; then
    echo "Potential committed secret detected."
    exit 1
fi

git diff --check

git_commit_if_needed \
    "test(docs): complete sprint-01 verification and documentation"

push_current_branch

rm -f -- "${SCRIPT_PATH}" express-cloud-sprint-01-files-only.sh express-cloud-sprint-01-v5.sh

echo
echo "============================================================"
echo "SPRINT ${SPRINT} COMPLETED"
echo "============================================================"
echo
echo "Expected commits:"
echo "  feat(sprint-01): establish database-independent Laravel foundation"
echo "  test(docs): complete sprint-01 verification and documentation"
echo
echo "The Sprint 1 shell script removed itself."
