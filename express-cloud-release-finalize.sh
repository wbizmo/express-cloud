#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_PATH="$(readlink -f "$0")"
LOG_FILE="/tmp/express-cloud-release-finalize-$(date -u +%Y%m%dT%H%M%SZ).log"
SKIP_PUSH="${SKIP_PUSH:-0}"

exec > >(tee -a "${LOG_FILE}") 2>&1

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "EXPRESS CLOUD RELEASE FINALIZATION FAILED"
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

section "Release finalization preflight"

if [[ ! -d .git || ! -f artisan || ! -f release/build-release.sh ]]; then
    echo "Run this script from the Express Cloud repository root."
    exit 1
fi

for command in git php composer npm mysql mysqldump zip rsync; do
    command -v "${command}" >/dev/null 2>&1 || {
        echo "Required command missing: ${command}"
        exit 1
    }
done

git log --oneline --all \
    --grep='feat(sprint-19): implement double-entry accounting and release packaging' |
    grep -q . || {
        echo "Sprint 19 implementation commit is missing."
        exit 1
    }

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Tracked files contain uncommitted changes."
    git status --short
    exit 1
fi

section "Installing canonical release builder"

cat > release/build-release.sh <<'BASH'
#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

for command in php composer npm mysql mysqldump zip rsync; do
    command -v "${command}" >/dev/null 2>&1 || {
        echo "Required release command missing: ${command}"
        exit 1
    }
done

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${ROOT}/release"
STAGE="${OUT}/stage"
PACKAGE_ROOT="${STAGE}/express-cloud"

RELEASE_DB_HOST="${RELEASE_DB_HOST:-${DB_HOST:-127.0.0.1}}"
RELEASE_DB_PORT="${RELEASE_DB_PORT:-${DB_PORT:-3306}}"
RELEASE_DB_USERNAME="${RELEASE_DB_USERNAME:-${DB_USERNAME:-express_release}}"
RELEASE_DB_PASSWORD="${RELEASE_DB_PASSWORD:-${DB_PASSWORD:-}}"

: "${RELEASE_DB_PASSWORD:?Set RELEASE_DB_PASSWORD or DB_PASSWORD}"

INSTALL_COMPANY_NAME="${INSTALL_COMPANY_NAME:-Express Cloud}"
INSTALL_BRANCH_NAME="${INSTALL_BRANCH_NAME:-Head Office}"
INSTALL_COMPANY_ADDRESS="${INSTALL_COMPANY_ADDRESS:-Update after installation}"
INSTALL_COMPANY_PHONE="${INSTALL_COMPANY_PHONE:-}"
INSTALL_ADMIN_FIRST_NAME="${INSTALL_ADMIN_FIRST_NAME:-Williams}"
INSTALL_ADMIN_LAST_NAME="${INSTALL_ADMIN_LAST_NAME:-Ashibuogwu}"
INSTALL_ADMIN_EMAIL="${INSTALL_ADMIN_EMAIL:-}"

TARGET_APP_URL="${TARGET_APP_URL:-https://app.examplecompany.com}"
TARGET_DB_HOST="${TARGET_DB_HOST:-127.0.0.1}"
TARGET_DB_PORT="${TARGET_DB_PORT:-3306}"
TARGET_DB_DATABASE="${TARGET_DB_DATABASE:-express_cloud}"
TARGET_DB_USERNAME="${TARGET_DB_USERNAME:-CHANGE_ME}"
TARGET_DB_PASSWORD="${TARGET_DB_PASSWORD:-CHANGE_ME}"

generate_base64_key() {
    php -r 'echo "base64:".base64_encode(random_bytes(32));'
}

generate_hex_key() {
    php -r 'echo bin2hex(random_bytes(32));'
}

generate_secret() {
    php -r 'echo bin2hex(random_bytes(24));'
}

APP_KEY="${APP_KEY:-$(generate_base64_key)}"
DATA_ENCRYPTION_KEY="${DATA_ENCRYPTION_KEY:-$(generate_base64_key)}"
BLIND_INDEX_KEY="${BLIND_INDEX_KEY:-$(generate_hex_key)}"
BACKUP_ENCRYPTION_KEY="${BACKUP_ENCRYPTION_KEY:-$(generate_base64_key)}"
CRON_PATH_SECRET="${CRON_PATH_SECRET:-$(generate_secret)}"

ADMIN_KEY="${INSTALL_ADMIN_KEY:-}"

if [[ -z "${ADMIN_KEY}" ]]; then
    ADMIN_KEY="$(php -r '
        $alphabet="ABCDEFGHJKMNPQRSTUVWXYZ23456789";
        $raw="";
        for($i=0;$i<8;$i++){
            $raw.=$alphabet[random_int(0,strlen($alphabet)-1)];
        }
        echo substr($raw,0,4)."-".substr($raw,4,4);
    ')"
fi

TEMP_DB="express_cloud_release_$(date -u +%Y%m%d%H%M%S)_$RANDOM"

cleanup() {
    MYSQL_PWD="${RELEASE_DB_PASSWORD}" mysql \
        -h "${RELEASE_DB_HOST}" \
        -P "${RELEASE_DB_PORT}" \
        -u "${RELEASE_DB_USERNAME}" \
        -e "DROP DATABASE IF EXISTS \`${TEMP_DB}\`;" \
        >/dev/null 2>&1 || true

    rm -rf "${STAGE}"
}
trap cleanup EXIT

rm -rf "${STAGE}"
mkdir -p "${PACKAGE_ROOT}"

echo "Installing production PHP dependencies..."
composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

echo "Building production frontend assets..."
npm ci --ignore-scripts
npm run build

echo "Creating temporary release database..."
MYSQL_PWD="${RELEASE_DB_PASSWORD}" mysql \
    -h "${RELEASE_DB_HOST}" \
    -P "${RELEASE_DB_PORT}" \
    -u "${RELEASE_DB_USERNAME}" \
    -e "CREATE DATABASE \`${TEMP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

export APP_NAME="Express Cloud"
export APP_ENV=production
export APP_KEY
export APP_DEBUG=false
export APP_URL="${TARGET_APP_URL}"
export FORCE_HTTPS=true

export DB_CONNECTION=mysql
export DB_HOST="${RELEASE_DB_HOST}"
export DB_PORT="${RELEASE_DB_PORT}"
export DB_DATABASE="${TEMP_DB}"
export DB_USERNAME="${RELEASE_DB_USERNAME}"
export DB_PASSWORD="${RELEASE_DB_PASSWORD}"

export DATA_ENCRYPTION_KEY
export BLIND_INDEX_KEY
export BACKUP_ENCRYPTION_KEY
export CRON_PATH_SECRET
export DATA_ENCRYPTION_VERSION=1

export INSTALL_COMPANY_NAME
export INSTALL_BRANCH_NAME
export INSTALL_COMPANY_ADDRESS
export INSTALL_COMPANY_PHONE
export INSTALL_ADMIN_FIRST_NAME
export INSTALL_ADMIN_LAST_NAME
export INSTALL_ADMIN_EMAIL
export INSTALL_ADMIN_KEY="${ADMIN_KEY}"

echo "Migrating and seeding release database..."
php artisan config:clear
php artisan migrate:fresh --seed --force
php artisan accounting:sync
php artisan optimize:clear

echo "Exporting installation SQL..."
MYSQL_PWD="${RELEASE_DB_PASSWORD}" mysqldump \
    -h "${RELEASE_DB_HOST}" \
    -P "${RELEASE_DB_PORT}" \
    -u "${RELEASE_DB_USERNAME}" \
    --single-transaction \
    --routines \
    --triggers \
    --hex-blob \
    --default-character-set=utf8mb4 \
    --set-gtid-purged=OFF \
    --no-tablespaces \
    "${TEMP_DB}" > "${OUT}/express-cloud-install.sql"

grep -q 'CREATE TABLE `accounts`' \
    "${OUT}/express-cloud-install.sql"
grep -q 'CREATE TABLE `journal_entries`' \
    "${OUT}/express-cloud-install.sql"
grep -q 'CREATE TABLE `ledger_accounts`' \
    "${OUT}/express-cloud-install.sql"
grep -q 'INSERT INTO `migrations`' \
    "${OUT}/express-cloud-install.sql"

cat > "${OUT}/FIRST_LOGIN.txt" <<EOF
Express Cloud First Login
=========================

Administrator: ${INSTALL_ADMIN_FIRST_NAME} ${INSTALL_ADMIN_LAST_NAME}
Access key: ${ADMIN_KEY}

This access key is shown once.

After installation:
1. Sign in.
2. Confirm company and branch details.
3. Rotate this access key.
4. Delete FIRST_LOGIN.txt from the server.
EOF

cat > "${OUT}/PRODUCTION.env" <<EOF
APP_NAME="Express Cloud"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${TARGET_APP_URL}
ASSET_URL=
APP_VERSION=1.0.0
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
DB_HOST=${TARGET_DB_HOST}
DB_PORT=${TARGET_DB_PORT}
DB_DATABASE=${TARGET_DB_DATABASE}
DB_USERNAME=${TARGET_DB_USERNAME}
DB_PASSWORD=${TARGET_DB_PASSWORD}

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
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

EXPRESS_CLOUD_CURRENCY=NGN
EXPRESS_CLOUD_CURRENCY_SYMBOL=₦
EXPRESS_CLOUD_MONEY_SCALE=100

CRON_ENABLED=false
CRON_PATH_SECRET=${CRON_PATH_SECRET}

BACKUP_ENABLED=false
BACKUP_EMAIL=
BACKUP_ENCRYPTION_KEY=${BACKUP_ENCRYPTION_KEY}
BACKUP_DISK=local
BACKUP_DIRECTORY=backups
BACKUP_RETENTION_DAYS=30
BACKUP_INCLUDE_UPLOADS=true

DATA_ENCRYPTION_KEY=${DATA_ENCRYPTION_KEY}
BLIND_INDEX_KEY=${BLIND_INDEX_KEY}
DATA_ENCRYPTION_VERSION=1

VITE_APP_NAME="\${APP_NAME}"
EOF

chmod 600 \
    "${OUT}/PRODUCTION.env" \
    "${OUT}/FIRST_LOGIN.txt"

echo "Staging application package..."
rsync -a \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='release/stage' \
    --exclude='release/*.zip' \
    --exclude='release/express-cloud-install.sql' \
    --exclude='release/FIRST_LOGIN.txt' \
    --exclude='release/PRODUCTION.env' \
    --exclude='express-cloud-sprint-*.sh' \
    --exclude='express-cloud-sprints-*.sh' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/app/backups/*' \
    --exclude='public/hot' \
    "${ROOT}/" "${PACKAGE_ROOT}/"

cp "${OUT}/express-cloud-install.sql" \
    "${PACKAGE_ROOT}/express-cloud-install.sql"
cp "${OUT}/FIRST_LOGIN.txt" \
    "${PACKAGE_ROOT}/FIRST_LOGIN.txt"
cp "${OUT}/PRODUCTION.env" \
    "${PACKAGE_ROOT}/.env"

test -f "${PACKAGE_ROOT}/artisan"
test -d "${PACKAGE_ROOT}/vendor"
test -f "${PACKAGE_ROOT}/public/build/manifest.json"
test -f "${PACKAGE_ROOT}/.env"
test -f "${PACKAGE_ROOT}/.env.example"
test -f "${PACKAGE_ROOT}/express-cloud-install.sql"
test -f "${PACKAGE_ROOT}/FIRST_LOGIN.txt"

(
    cd "${STAGE}"

    find express-cloud -type f -print0 \
        | sort -z \
        | xargs -0 sha256sum \
        > express-cloud/RELEASE_MANIFEST.sha256

    zip -qr \
        "${OUT}/express-cloud-release.zip" \
        express-cloud
)

test -s "${OUT}/express-cloud-release.zip"
test -s "${OUT}/express-cloud-install.sql"
test -s "${OUT}/FIRST_LOGIN.txt"
test -s "${OUT}/PRODUCTION.env"

echo
echo "============================================================"
echo "EXPRESS CLOUD RELEASE CREATED"
echo "============================================================"
echo
echo "Generated:"
echo "  ${OUT}/express-cloud-release.zip"
echo "  ${OUT}/express-cloud-install.sql"
echo "  ${OUT}/FIRST_LOGIN.txt"
echo "  ${OUT}/PRODUCTION.env"
echo
echo "The ZIP contains artisan, vendor/, built assets, .env,"
echo ".env.example, SQL, documentation, and first-login details."
BASH

chmod +x release/build-release.sh

section "Updating release documentation"

python3 - <<'PY'
from pathlib import Path

path = Path("docs/installation/INSTALLATION.md")
text = path.read_text()

addition = """
## Packaged environment

The release ZIP contains a generated production `.env`. Its application,
data-encryption, blind-index, backup-encryption, and cron secrets are the same
ones used while producing the seeded SQL database.

Before opening the application on the target server, update only the target
database connection, application URL, mail configuration, and hosting-specific
values. Do not replace the generated encryption keys after importing the SQL,
because encrypted seeded values depend on them.
"""

if "## Packaged environment" not in text:
    text = text.rstrip() + "\n\n" + addition.strip() + "\n"

path.write_text(text)

path = Path("docs/installation/RELEASE_BUILD.md")
text = path.read_text()

addition = """
## Generated secrets

The release builder generates `APP_KEY`, `DATA_ENCRYPTION_KEY`,
`BLIND_INDEX_KEY`, `BACKUP_ENCRYPTION_KEY`, and `CRON_PATH_SECRET` when they
are not supplied. It uses those values while seeding and packages the same
values in the production `.env`.

Release database credentials are separate from the target database values and
are never written into the customer package.
"""

if "## Generated secrets" not in text:
    text = text.rstrip() + "\n\n" + addition.strip() + "\n"

path.write_text(text)
PY

touch .gitignore

for ignored in \
    '/release/express-cloud-release.zip' \
    '/release/express-cloud-install.sql' \
    '/release/FIRST_LOGIN.txt' \
    '/release/PRODUCTION.env' \
    '/release/stage/'
do
    grep -qxF "${ignored}" .gitignore || echo "${ignored}" >> .gitignore
done

section "Validating corrected release builder"

bash -n release/build-release.sh
git diff --check

section "Building final release"

bash release/build-release.sh

section "Committing final release tooling"

git add \
    .gitignore \
    release/build-release.sh \
    docs/installation/INSTALLATION.md \
    docs/installation/RELEASE_BUILD.md

if ! git diff --cached --quiet; then
    git commit -m \
        "fix(release): generate production environment and finalize package builder"

    if [[ "${SKIP_PUSH}" != "1" ]]; then
        git push -u origin "$(git branch --show-current)"
    else
        echo "SKIP_PUSH=1; push skipped."
    fi
else
    echo "Release tooling already matches the canonical version."
fi

section "Final verification"

test -s release/express-cloud-release.zip
test -s release/express-cloud-install.sql
test -s release/FIRST_LOGIN.txt
test -s release/PRODUCTION.env

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Tracked repository state is not clean."
    git status --short
    exit 1
fi

rm -f -- "${SCRIPT_PATH}"

echo
echo "============================================================"
echo "EXPRESS CLOUD RELEASE FINALIZED"
echo "============================================================"
echo
echo "Log: ${LOG_FILE}"
echo
echo "Artifacts:"
echo "  release/express-cloud-release.zip"
echo "  release/express-cloud-install.sql"
echo "  release/FIRST_LOGIN.txt"
echo "  release/PRODUCTION.env"
echo
echo "Final commits:"
git log --oneline -5
