#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SPRINT="01"
PROJECT_SLUG="${PROJECT_SLUG:-express-cloud}"
DEFAULT_BRANCH="${DEFAULT_BRANCH:-main}"
REPO_VISIBILITY="${REPO_VISIBILITY:-private}"
SKIP_PUSH="${SKIP_PUSH:-0}"
SCRIPT_PATH="$(readlink -f "$0")"
SCRIPT_NAME="$(basename "$0")"

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "SPRINT ${SPRINT} FAILED"
    echo "Line: ${line_no}"
    echo "Exit code: ${exit_code}"
    echo "The sprint script was retained for inspection."
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

version_at_least() {
    php -r "exit(version_compare('$1', '$2', '>=') ? 0 : 1);"
}

prepare_repository() {
    if [[ ! -d .git ]]; then
        git init -b "${DEFAULT_BRANCH}"
    fi

    if git log --oneline --all --grep='sprint-01' | grep -q .; then
        echo "Sprint 1 already exists in Git history."
        git log --oneline --all --grep='sprint-01'
        exit 1
    fi

    if [[ -n "$(git status --porcelain)" ]]; then
        echo "Cleaning abandoned uncommitted Sprint 1 output..."
        git reset --hard HEAD
        git clean -fdx -e "${SCRIPT_NAME}"
    fi

    local unexpected
    unexpected="$(find . -mindepth 1 -maxdepth 1 \
        ! -name .git \
        ! -name "${SCRIPT_NAME}" \
        -printf '%f\n' | sort || true)"

    if [[ -n "${unexpected}" ]]; then
        echo "Sprint 1 expects an otherwise blank repository."
        echo "Unexpected files:"
        echo "${unexpected}"
        exit 1
    fi
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

    if git remote get-url origin >/dev/null 2>&1; then
        git push -u origin "$(git branch --show-current)"
        return 0
    fi

    if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
        gh repo create "${PROJECT_SLUG}" \
            "--${REPO_VISIBILITY}" \
            --source=. \
            --remote=origin \
            --push
        return 0
    fi

    echo "No origin remote exists and GitHub CLI is not authenticated."
    echo "Either configure origin or rerun with SKIP_PUSH=1."
    exit 1
}

section "Sprint ${SPRINT}: preflight"

require_command git
require_command php
require_command composer
require_command node
require_command npm

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"

if ! version_at_least "${PHP_VERSION}" "8.3.0"; then
    echo "Laravel 13 requires PHP 8.3 or newer."
    echo "Detected PHP ${PHP_VERSION}."
    exit 1
fi

prepare_repository

git config user.name >/dev/null 2>&1 || git config user.name "Williams Ashibuogwu"
git config user.email >/dev/null 2>&1 || git config user.email "admin@express.zivora.com"

section "Creating Laravel application without database services"

rm -rf /tmp/express-cloud-bootstrap

composer create-project \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    laravel/laravel:^13.0 \
    /tmp/express-cloud-bootstrap

tar --exclude=.git \
    -C /tmp/express-cloud-bootstrap \
    -cf - . | tar -C . -xf -

rm -rf /tmp/express-cloud-bootstrap
rm -f database/database.sqlite

composer require \
    --no-interaction \
    --no-scripts \
    livewire/livewire:^4.0

composer require \
    --dev \
    --no-interaction \
    --no-scripts \
    larastan/larastan

npm install
npm install --save-dev tailwindcss @tailwindcss/vite

mkdir -p \
    app/Support/Money \
    app/Support/Security \
    docs/architecture/decisions \
    docs/architecture \
    docs/deployment \
    docs/testing \
    docs/sprints

cat > .env.example <<'ENV'
APP_NAME="Express Cloud"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.examplecompany.com
ASSET_URL=
APP_VERSION=1.0.0-dev
FORCE_HTTPS=true

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_NG
APP_TIMEZONE=Africa/Lagos

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=express_cloud
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file
CACHE_PREFIX=express_cloud

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="no-reply@express.zivora.com"
MAIL_FROM_NAME="${APP_NAME}"

EXPRESS_CLOUD_CURRENCY=NGN
EXPRESS_CLOUD_CURRENCY_SYMBOL=₦
EXPRESS_CLOUD_MONEY_SCALE=100

CRON_ENABLED=false
CRON_PATH_SECRET=

BACKUP_ENABLED=false
BACKUP_EMAIL=
BACKUP_ENCRYPTION_KEY=

DATA_ENCRYPTION_KEY=
BLIND_INDEX_KEY=
DATA_ENCRYPTION_VERSION=1

VITE_APP_NAME="${APP_NAME}"
ENV

cp .env.example .env

python3 - <<'PY'
from pathlib import Path

path = Path(".env")
text = path.read_text()
text = text.replace("APP_ENV=production", "APP_ENV=local")
text = text.replace("APP_DEBUG=false", "APP_DEBUG=true")
text = text.replace(
    "APP_URL=https://app.examplecompany.com",
    "APP_URL=http://localhost:8000",
)
text = text.replace("FORCE_HTTPS=true", "FORCE_HTTPS=false")
text = text.replace("SESSION_SECURE_COOKIE=true", "SESSION_SECURE_COOKIE=false")
text = text.replace("LOG_LEVEL=warning", "LOG_LEVEL=debug")
path.write_text(text)
PY

php artisan key:generate --ansi

cat > config/express-cloud.php <<'PHP'
<?php

declare(strict_types=1);

return [
    'version' => env('APP_VERSION', '1.0.0-dev'),

    'currency' => [
        'code' => 'NGN',
        'symbol' => '₦',
        'minor_unit_scale' => 100,
    ],

    'http' => [
        'force_https' => (bool) env('FORCE_HTTPS', false),
    ],

    'security' => [
        'data_encryption_key' => env('DATA_ENCRYPTION_KEY'),
        'blind_index_key' => env('BLIND_INDEX_KEY'),
        'data_encryption_version' => (int) env('DATA_ENCRYPTION_VERSION', 1),
    ],

    'infrastructure' => [
        'target_database' => 'mysql',
        'redis_optional' => true,
    ],
];
PHP

cat > app/Support/Money/Naira.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class Naira implements JsonSerializable, Stringable
{
    public const string CURRENCY = 'NGN';

    public const string SYMBOL = '₦';

    public const int MINOR_UNIT_SCALE = 100;

    public function __construct(public int $kobo)
    {
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromKobo(int $kobo): self
    {
        return new self($kobo);
    }

    public static function fromNaira(string|int $naira): self
    {
        $normalized = trim((string) $naira);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException(
                'Money must be a valid NGN amount with at most two decimal places.',
            );
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        $kobo = ((int) $whole * self::MINOR_UNIT_SCALE) + (int) $fraction;

        return new self($negative ? -$kobo : $kobo);
    }

    public function add(self $other): self
    {
        return new self($this->kobo + $other->kobo);
    }

    public function subtract(self $other): self
    {
        return new self($this->kobo - $other->kobo);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self($this->kobo * $quantity);
    }

    public function format(): string
    {
        $absolute = abs($this->kobo);
        $whole = intdiv($absolute, self::MINOR_UNIT_SCALE);
        $fraction = $absolute % self::MINOR_UNIT_SCALE;
        $sign = $this->kobo < 0 ? '-' : '';

        if ($fraction === 0) {
            return $sign.self::SYMBOL.number_format($whole);
        }

        return sprintf(
            '%s%s%s.%02d',
            $sign,
            self::SYMBOL,
            number_format($whole),
            $fraction,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'currency' => self::CURRENCY,
            'amount_minor' => $this->kobo,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
PHP

cat > app/Support/Security/EncryptedValue.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

final readonly class EncryptedValue
{
    private Encrypter $encrypter;

    public function __construct(
        ?string $base64Key = null,
        public int $version = 1,
    ) {
        $key = $base64Key ?? (string) config(
            'express-cloud.security.data_encryption_key',
        );

        if ($key === '') {
            throw new InvalidArgumentException(
                'DATA_ENCRYPTION_KEY is not configured.',
            );
        }

        $decoded = str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true)
            : $key;

        if (! is_string($decoded) || strlen($decoded) !== 32) {
            throw new InvalidArgumentException(
                'DATA_ENCRYPTION_KEY must resolve to exactly 32 bytes.',
            );
        }

        $this->encrypter = new Encrypter($decoded, 'AES-256-CBC');
    }

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            throw new InvalidArgumentException(
                'Sensitive values cannot be empty.',
            );
        }

        return json_encode([
            'v' => $this->version,
            'ciphertext' => $this->encrypter->encryptString($plaintext),
        ], JSON_THROW_ON_ERROR);
    }

    public function decrypt(string $payload): string
    {
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! isset($decoded['ciphertext'])) {
            throw new DecryptException('Encrypted payload is malformed.');
        }

        return $this->encrypter->decryptString(
            (string) $decoded['ciphertext'],
        );
    }
}
PHP

cat > app/Support/Security/BlindIndex.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

final readonly class BlindIndex
{
    public function __construct(private ?string $key = null)
    {
    }

    public function make(string $value): string
    {
        $normalized = self::normalize($value);
        $key = $this->key ?? (string) config(
            'express-cloud.security.blind_index_key',
        );

        if ($key === '') {
            throw new InvalidArgumentException(
                'BLIND_INDEX_KEY is not configured.',
            );
        }

        return hash_hmac('sha256', $normalized, $key);
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
PHP

cat > app/Support/Security/LoginKeyGenerator.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

final class LoginKeyGenerator
{
    public const string ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const int RAW_LENGTH = 8;

    public function generate(): string
    {
        $characters = [];

        for ($index = 0; $index < self::RAW_LENGTH; $index++) {
            $characters[] = self::ALPHABET[
                random_int(0, strlen(self::ALPHABET) - 1)
            ];
        }

        return self::format(implode('', $characters));
    }

    public static function normalize(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['-', ' '], '', $normalized);

        if (
            strlen($normalized) !== self::RAW_LENGTH
            || strspn($normalized, self::ALPHABET) !== self::RAW_LENGTH
        ) {
            throw new InvalidArgumentException(
                'Access key must contain exactly eight approved characters.',
            );
        }

        return $normalized;
    }

    public static function format(string $value): string
    {
        $normalized = self::normalize($value);

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }
}
PHP

cat > app/Support/Security/SensitiveData.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Security;

final class SensitiveData
{
    /**
     * @return array<int, string>
     */
    public static function forbiddenLogFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'login_key',
            'login_key_encrypted',
            'login_key_blind_index',
            'data_encryption_key',
            'blind_index_key',
            'backup_encryption_key',
            'cron_path_secret',
            'recovery_code',
            'api_secret',
        ];
    }
}
PHP

cat > app/Providers/AppServiceProvider.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::preventAccessingMissingAttributes(! app()->isProduction());

        if ((bool) config('express-cloud.http.force_https')) {
            URL::forceScheme('https');
        }
    }
}
PHP

cat > vite.config.js <<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
JS

cat > resources/css/app.css <<'CSS'
@import "tailwindcss";

@source "../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php";
@source "../../vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php";
@source "../views/**/*.blade.php";
@source "../js/**/*.js";

:root {
    color-scheme: light;
    --zivora-navy: #0b1f3a;
    --zivora-blue: #2563eb;
    --zivora-red: #dc2626;
    --surface: #f5f7fb;
    --panel: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
}

html {
    background: var(--surface);
    color: var(--text);
}
CSS

cat > routes/web.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Express Cloud entry route
|--------------------------------------------------------------------------
|
| Sprint 3 will replace this foundation screen with the shared admin/staff
| login page. The production root route remains the authentication entry.
|
*/

Route::view('/', 'welcome')->name('home');
PHP

cat > resources/views/welcome.blade.php <<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Express Cloud by Zivora">
    <title>Express Cloud by Zivora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <main class="grid min-h-screen place-items-center p-6">
        <section class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                by Zivora
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Express Cloud
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Engineering foundation ready. The shared login screen will
                replace this page in Sprint 3.
            </p>
        </section>
    </main>
</body>
</html>
BLADE

cat > phpstan.neon <<'NEON'
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
        - config
        - routes
        - tests

    level: 6
    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: true
NEON

python3 - <<'PY'
import json
from pathlib import Path

path = Path("composer.json")
data = json.loads(path.read_text())

scripts = data.setdefault("scripts", {})
scripts["analyse"] = [
    "vendor/bin/phpstan analyse --memory-limit=1G"
]
scripts["format"] = [
    "vendor/bin/pint"
]
scripts["format:test"] = [
    "vendor/bin/pint --test"
]
scripts["quality:no-db"] = [
    "@format:test",
    "@analyse",
    "@test"
]

path.write_text(json.dumps(data, indent=4) + "\n")
PY

cat > .editorconfig <<'EOF'
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = space
indent_size = 4
trim_trailing_whitespace = true

[*.{yml,yaml,json}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false
EOF

composer dump-autoload --no-interaction
php artisan optimize:clear
npm run build
vendor/bin/pint

section "Sprint 1 implementation verification"

php -v
composer validate --strict
composer audit
php artisan about
php artisan route:list
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
npm audit --audit-level=high
npm run build

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

rm -f -- "${SCRIPT_PATH}"

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
