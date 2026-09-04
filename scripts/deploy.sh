#!/usr/bin/env bash
set -Eeuo pipefail

: "${APP_DIR:?APP_DIR is required}"

RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"
ARCHIVE="/tmp/simpram-release.tar.gz"

echo "==> Preparing release ${TIMESTAMP}"

mkdir -p "${RELEASES_DIR}" "${SHARED_DIR}/storage"
mkdir -p "${SHARED_DIR}/storage/app/public"
mkdir -p "${SHARED_DIR}/storage/framework/cache/data"
mkdir -p "${SHARED_DIR}/storage/framework/sessions"
mkdir -p "${SHARED_DIR}/storage/framework/views"
mkdir -p "${SHARED_DIR}/storage/logs"

if [[ ! -f "${SHARED_DIR}/.env" ]]; then
  echo "ERROR: ${SHARED_DIR}/.env does not exist."
  echo "Create production/staging .env on the server before the first deployment."
  exit 1
fi

mkdir -p "${RELEASE_DIR}"
tar -xzf "${ARCHIVE}" -C "${RELEASE_DIR}"

rm -rf "${RELEASE_DIR}/storage"
ln -s "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
ln -s "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"

cd "${RELEASE_DIR}"

echo "==> Installing production PHP dependencies"
composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-progress

echo "==> Clearing stale caches"
php artisan optimize:clear

echo "==> Enabling maintenance mode"
php artisan down --retry=60 || true

rollback() {
  echo "Deployment failed."
  if [[ -L "${CURRENT_LINK}" ]]; then
    cd "${CURRENT_LINK}"
    php artisan up || true
  fi
}
trap rollback ERR

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Optimizing Laravel"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ensuring public storage link"
php artisan storage:link || true

echo "==> Activating release"
ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}"

cd "${CURRENT_LINK}"

echo "==> Restarting queue workers"
php artisan queue:restart || true

echo "==> Application online"
php artisan up || true

trap - ERR

echo "==> Removing old releases"
cd "${RELEASES_DIR}"
ls -1dt */ 2>/dev/null | tail -n +6 | xargs -r rm -rf

rm -f "${ARCHIVE}"

echo "Deployment completed: ${RELEASE_DIR}"
