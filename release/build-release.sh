#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

for command in php composer npm mysql mysqldump zip rsync; do
    command -v "${command}" >/dev/null 2>&1 || {
        echo "Required release command missing: ${command}"
        exit 1
    }
done

: "${DB_HOST:?Set DB_HOST}"
: "${DB_PORT:=3306}"
: "${DB_USERNAME:?Set DB_USERNAME}"
: "${DB_PASSWORD:?Set DB_PASSWORD}"
: "${INSTALL_COMPANY_NAME:?Set INSTALL_COMPANY_NAME}"
: "${INSTALL_ADMIN_FIRST_NAME:?Set INSTALL_ADMIN_FIRST_NAME}"
: "${INSTALL_ADMIN_LAST_NAME:?Set INSTALL_ADMIN_LAST_NAME}"
: "${DATA_ENCRYPTION_KEY:?Set DATA_ENCRYPTION_KEY}"
: "${BLIND_INDEX_KEY:?Set BLIND_INDEX_KEY}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${ROOT}/release"
STAGE="${OUT}/stage"
TEMP_DB="express_cloud_release_$(date -u +%Y%m%d%H%M%S)_$RANDOM"
ADMIN_KEY="${INSTALL_ADMIN_KEY:-}"

if [[ -z "${ADMIN_KEY}" ]]; then
    ADMIN_KEY="$(php -r '
        $alphabet="ABCDEFGHJKMNPQRSTUVWXYZ23456789";
        $raw="";
        for($i=0;$i<8;$i++){$raw.=$alphabet[random_int(0,strlen($alphabet)-1)];}
        echo substr($raw,0,4)."-".substr($raw,4,4);
    ')"
fi

cleanup() {
    MYSQL_PWD="${DB_PASSWORD}" mysql \
        -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" \
        -e "DROP DATABASE IF EXISTS \`${TEMP_DB}\`;" >/dev/null 2>&1 || true
    rm -rf "${STAGE}"
}
trap cleanup EXIT

rm -rf "${STAGE}"
mkdir -p "${STAGE}"

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --ignore-scripts
npm run build

MYSQL_PWD="${DB_PASSWORD}" mysql \
    -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" \
    -e "CREATE DATABASE \`${TEMP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

export DB_CONNECTION=mysql
export DB_DATABASE="${TEMP_DB}"
export INSTALL_ADMIN_KEY="${ADMIN_KEY}"
export APP_ENV=production
export APP_DEBUG=false

php artisan config:clear
php artisan migrate:fresh --seed --force
php artisan accounting:sync
php artisan optimize:clear

MYSQL_PWD="${DB_PASSWORD}" mysqldump \
    -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" \
    --single-transaction \
    --routines \
    --triggers \
    --hex-blob \
    --default-character-set=utf8mb4 \
    --set-gtid-purged=OFF \
    --no-tablespaces \
    "${TEMP_DB}" > "${OUT}/express-cloud-install.sql"

grep -q 'CREATE TABLE `accounts`' "${OUT}/express-cloud-install.sql"
grep -q 'CREATE TABLE `journal_entries`' "${OUT}/express-cloud-install.sql"
grep -q 'CREATE TABLE `ledger_accounts`' "${OUT}/express-cloud-install.sql"

cat > "${OUT}/FIRST_LOGIN.txt" <<EOF
Express Cloud First Login
=========================

Administrator: ${INSTALL_ADMIN_FIRST_NAME} ${INSTALL_ADMIN_LAST_NAME}
Access key: ${ADMIN_KEY}

This access key is shown once. Store it securely, complete first login,
rotate it immediately after handover, and delete this file.
EOF

rsync -a \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='release/stage' \
    --exclude='release/*.zip' \
    --exclude='release/express-cloud-install.sql' \
    --exclude='release/FIRST_LOGIN.txt' \
    --exclude='express-cloud-sprint-*.sh' \
    --exclude='express-cloud-sprints-*.sh' \
    --exclude='storage/logs/*' \
    --exclude='storage/app/backups/*' \
    --exclude='public/hot' \
    "${ROOT}/" "${STAGE}/express-cloud/"

cp "${OUT}/express-cloud-install.sql" \
    "${STAGE}/express-cloud/express-cloud-install.sql"
cp "${OUT}/FIRST_LOGIN.txt" \
    "${STAGE}/express-cloud/FIRST_LOGIN.txt"

(
    cd "${STAGE}"
    find express-cloud -type f -print0 \
        | sort -z \
        | xargs -0 sha256sum > express-cloud/RELEASE_MANIFEST.sha256
    zip -qr "${OUT}/express-cloud-release.zip" express-cloud
)

test -s "${OUT}/express-cloud-release.zip"
test -s "${OUT}/express-cloud-install.sql"
test -s "${OUT}/FIRST_LOGIN.txt"

echo "Release created:"
echo "  ${OUT}/express-cloud-release.zip"
echo "  ${OUT}/express-cloud-install.sql"
echo "  ${OUT}/FIRST_LOGIN.txt"
